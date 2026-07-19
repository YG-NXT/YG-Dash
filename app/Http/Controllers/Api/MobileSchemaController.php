<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileSchemaController extends Controller
{
    use ApiResponseTrait;

    private array $modules = [
        'dashboard'        => ['label' => 'Dashboard',  'icon' => 'grid',        'list' => null,           'desc' => 'Workspace overview and quick stats.'],
        'hrm'             => ['label' => 'HRM',        'icon' => 'people',      'list' => 'staff',        'desc' => 'Employees, attendance, leaves and holidays.'],
        'taskly'          => ['label' => 'Projects',   'icon' => 'folder',      'list' => 'clients',      'desc' => 'Tasks, projects and client deliverables.'],
        'lead'            => ['label' => 'Leads',      'icon' => 'trending-up', 'list' => 'clients',      'desc' => 'Capture, track and convert leads.'],
        'pos'             => ['label' => 'POS',        'icon' => 'cart',        'list' => 'clients',      'desc' => 'Point of sale and retail billing.'],
        'productservice'  => ['label' => 'Products',   'icon' => 'cube',        'list' => 'vendors',      'desc' => 'Products, services and inventory.'],
        'account'         => ['label' => 'Accounting', 'icon' => 'receipt',     'list' => 'clients',      'desc' => 'Invoices, expenses and reporting.'],
        'profile'         => ['label' => 'Profile',    'icon' => 'person',      'list' => null,           'desc' => 'Your personal account details.'],
    ];

    public function menu(Request $request)
    {
        $user = $request->user();
        $owner = $user->created_by ?? $user->id;

        $items = [];
        foreach ($this->modules as $key => $meta) {
            if ($key === 'dashboard' || $key === 'profile') {
                $active = true;
            } else {
                $active = \Module_is_active(ucfirst($key), $owner);
            }
            if (!$active) {
                continue;
            }
            $items[] = [
                'key'   => $key,
                'label' => $meta['label'],
                'icon'  => $meta['icon'],
                'screen'=> $key,
            ];
        }

        return $this->successResponse($items, 'Menu retrieved successfully.');
    }

    public function screen(Request $request, string $slug)
    {
        if (!array_key_exists($slug, $this->modules)) {
            return $this->errorResponse('Screen not found.', null, 404);
        }

        $meta = $this->modules[$slug];
        $components = [];

        $components[] = [
            'type'    => 'screen-header',
            'title'   => $meta['label'],
            'subtitle'=> $meta['desc'],
        ];

        if ($slug === 'dashboard') {
            $components = array_merge($components, $this->dashboardComponents($request));
        } elseif ($slug === 'profile') {
            $components[] = $this->profileFormComponent($request);
            $components[] = [
                'type'    => 'button',
                'label'   => 'Change password',
                'variant' => 'outline',
                'navigate'=> 'ChangePassword',
            ];
        } else {
            if (!empty($meta['list'])) {
                $components[] = [
                    'type'    => 'section',
                    'title'   => 'Records',
                    'children' => [
                        [
                            'type' => 'list',
                            'data' => $this->resolveList($meta['list'], $request),
                        ],
                    ],
                ];
            } else {
                $components[] = [
                    'type' => 'card',
                    'title' => $meta['label'],
                    'body'  => 'This module is available on the web app and will stream to the mobile app as its schema is enabled.',
                ];
            }

            $components[] = [
                'type' => 'cards',
                'items' => $this->moduleShortcuts($slug),
            ];
        }

        $data = [
            'slug'       => $slug,
            'title'      => $meta['label'],
            'components' => $components,
        ];

        return $this->successResponse($data, 'Screen retrieved successfully.');
    }

    private function dashboardComponents(Request $request): array
    {
        $owner = $request->user()->created_by ?? $request->user()->id;

        $staff   = User::where('created_by', $owner)->emp()->count();
        $clients = User::where('type', 'client')->where('created_by', $owner)->count();
        $vendors = User::where('type', 'vendor')->where('created_by', $owner)->count();

        $cards = [];
        foreach ($this->modules as $key => $meta) {
            if ($key === 'dashboard' || $key === 'profile') {
                continue;
            }
            if (!\Module_is_active(ucfirst($key), $owner)) {
                continue;
            }
            $cards[] = [
                'label' => $meta['label'],
                'icon'  => $meta['icon'],
                'screen'=> $key,
            ];
        }

        return [
            [
                'type'  => 'stats',
                'items' => [
                    ['label' => 'Staff',   'value' => (string) $staff,   'icon' => 'people'],
                    ['label' => 'Clients', 'value' => (string) $clients, 'icon' => 'briefcase'],
                    ['label' => 'Vendors', 'value' => (string) $vendors, 'icon' => 'storefront'],
                ],
            ],
            [
                'type'  => 'cards',
                'title' => 'Modules',
                'items' => $cards,
            ],
        ];
    }

    private function profileFormComponent(Request $request): array
    {
        $user = $request->user();

        return [
            'type'    => 'form',
            'action'  => '/edit-profile',
            'method'  => 'POST',
            'submitLabel' => 'Save changes',
            'fields'  => [
                ['name' => 'name',      'label' => 'Name',        'type' => 'text',     'required' => true,  'value' => $user->name],
                ['name' => 'email',     'label' => 'Email',       'type' => 'email',    'required' => true,  'value' => $user->email],
                ['name' => 'mobile_no', 'label' => 'Mobile',      'type' => 'tel',      'required' => false, 'value' => $user->mobile_no ?? ''],
            ],
        ];
    }

    private function moduleShortcuts(string $slug): array
    {
        return [
            [
                'label' => 'Open on web',
                'icon'  => 'open',
                'screen'=> null,
            ],
        ];
    }

    private function resolveList(?string $key, Request $request): array
    {
        if (!$key) {
            return [];
        }

        $owner = $request->user()->created_by ?? $request->user()->id;
        $query = null;

        if ($key === 'staff') {
            $query = User::where('created_by', $owner)->emp();
        } elseif ($key === 'clients') {
            $query = User::where('type', 'client')->where('created_by', $owner);
        } elseif ($key === 'vendors') {
            $query = User::where('type', 'vendor')->where('created_by', $owner);
        }

        if (!$query) {
            return [];
        }

        return $query->orderBy('id', 'desc')->limit(50)->get()->map(function ($user) {
            return [
                'id'      => $user->id,
                'title'   => $user->name,
                'subtitle'=> $user->email,
                'avatar'  => $user->avatar ? getImageUrlPrefix() . '/' . $user->avatar : getImageUrlPrefix() . '/avatar.png',
                'badge'   => $user->type ?? null,
            ];
        })->all();
    }
}
