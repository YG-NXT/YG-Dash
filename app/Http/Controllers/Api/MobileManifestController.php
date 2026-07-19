<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserActiveModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class MobileManifestController extends Controller
{
    public function manifest(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cacheKey = "mobile_manifest_{$user->id}_{$user->type}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            try {
                $modules = $this->getActiveModules($user);
                $navigation = $this->buildNavigation($modules);
                $screens = $this->buildScreens($modules);
                
                return [
                    'version' => '1.0.0',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => $user->type,
                        'avatar' => $user->avatar_url ?? null,
                        'permissions' => $this->getUserPermissions($user),
                    ],
                    'modules' => $modules,
                    'navigation' => $navigation,
                    'screens' => $screens,
                    'components' => $this->getComponentDefinitions(),
                ];
            } catch (\Throwable $e) {
                return [
                    'version' => '1.0.0',
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => $user->type,
                        'avatar' => $user->avatar_url ?? null,
                        'permissions' => ['view_dashboard'],
                    ],
                    'modules' => [],
                    'navigation' => [
                        'type' => 'bottom-tabs',
                        'tabs' => [],
                    ],
                    'screens' => [],
                    'components' => [],
                ];
            }
        });
    }

    private function getActiveModules($user): array
    {
        $modules = [];
        $owner = $user->created_by ?? $user->id;

        $moduleNames = ['Taskly', 'Account', 'Hrm', 'Lead', 'Pos', 'Helpdesk', 'Messenger', 'AiAgent', 'ProductService'];

        foreach ($moduleNames as $moduleName) {
            $isActive = true;
            if (function_exists('\Module_is_active')) {
                try {
                    $isActive = \Module_is_active($moduleName, $owner);
                } catch (\Throwable $e) {
                    $isActive = true;
                }
            }

            if (!$isActive) {
                continue;
            }

            $moduleData = [
                'id' => Str::lower($moduleName),
                'name' => $this->getModuleAlias($moduleName),
                'icon' => $this->getModuleIcon($moduleName),
                'color' => $this->getModuleColor($moduleName),
                'screens' => $this->getModuleScreens(Str::lower($moduleName), $user),
            ];

            $modules[] = $moduleData;
        }

        return $modules;
    }

    private function getModuleAlias(string $moduleName): string
    {
        return match (Str::lower($moduleName)) {
            'taskly' => 'Projects',
            'account' => 'Accounting',
            'hrm' => 'HRM',
            'lead' => 'CRM',
            'pos' => 'POS',
            'productservice' => 'Products',
            'helpdesk' => 'Helpdesk',
            'messenger' => 'Messages',
            'aiagent' => 'AI Assistant',
            default => $moduleName,
        };
    }

    private function getModuleIcon(string $moduleName): string
    {
        return match (Str::lower($moduleName)) {
            'taskly', 'project' => 'briefcase',
            'account', 'accounting' => 'bar-chart',
            'hrm' => 'users',
            'lead', 'crm' => 'user-plus',
            'pos' => 'shopping-cart',
            'productservice', 'product-service' => 'package',
            'helpdesk' => 'help-circle',
            'messenger' => 'message',
            'ai-agent' => 'cpu',
            default => 'grid',
        };
    }

    private function getModuleColor(string $moduleName): string
    {
        return match (Str::lower($moduleName)) {
            'taskly', 'project' => '#3B82F6',
            'account', 'accounting' => '#10B981',
            'hrm' => '#8B5CF6',
            'lead', 'crm' => '#F59E0B',
            'pos' => '#EF4444',
            'productservice', 'product-service' => '#6366F1',
            'helpdesk' => '#EC4899',
            'messenger' => '#06B6D4',
            'ai-agent' => '#8B5CF6',
            default => '#6B7280',
        };
    }

    private function getUserPermissions($user): array
    {
        $permissions = ['view_dashboard'];
        
        if ($user->type === 'super admin' || $user->type === 'admin') {
            return array_merge($permissions, [
                'view_users', 'manage_users', 'view_roles', 'manage_roles',
                'view_settings', 'manage_settings', 'view_plans', 'manage_plans',
            ]);
        }

        if ($user->role) {
            $permissions = array_merge($permissions, $user->role->permissions ?? []);
        }

        return $permissions;
    }

    private function buildScreens(array $modules): array
    {
        $screens = [];
        
        foreach ($modules as $module) {
            foreach ($module['screens'] as $screen) {
                $screens[$screen['id']] = $screen;
            }
        }

        $screens['profile'] = [
            'id' => 'profile',
            'type' => 'profile',
            'title' => 'Profile',
            'sections' => [
                [
                    'type' => 'fields',
                    'fields' => [
                        ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                        ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                        ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ],
                ],
            ],
        ];

        $screens['settings'] = [
            'id' => 'settings',
            'type' => 'settings',
            'title' => 'Settings',
            'sections' => [
                [
                    'title' => 'Account',
                    'items' => [
                        ['id' => 'change_password', 'label' => 'Change Password', 'icon' => 'lock', 'action' => 'change_password'],
                        ['id' => 'language', 'label' => 'Language', 'icon' => 'globe', 'action' => 'language'],
                    ],
                ],
                [
                    'title' => 'App',
                    'items' => [
                        ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bell', 'action' => 'toggle', 'default' => true],
                        ['id' => 'biometric', 'label' => 'Biometric Login', 'icon' => 'finger-print', 'action' => 'toggle', 'default' => false],
                    ],
                ],
                [
                    'title' => 'Danger',
                    'items' => [
                        ['id' => 'logout', 'label' => 'Logout', 'icon' => 'log-out', 'action' => 'logout', 'danger' => true],
                        ['id' => 'delete_account', 'label' => 'Delete Account', 'icon' => 'trash', 'action' => 'delete_account', 'danger' => true],
                    ],
                ],
            ],
        ];

        return $screens;
    }

    private function buildNavigation(array $modules): array
    {
        $tabs = [];
        
        foreach ($modules as $module) {
            $firstScreen = $module['screens'][0] ?? null;
            if (!$firstScreen) continue;

            $tabs[] = [
                'id' => $module['id'],
                'name' => $module['name'],
                'icon' => $module['icon'],
                'screenId' => $firstScreen['id'],
                'color' => $module['color'],
            ];
        }

        $tabs[] = [
            'id' => 'profile',
            'name' => 'Profile',
            'icon' => 'person',
            'screenId' => 'profile',
        ];

        return [
            'type' => 'bottom-tabs',
            'tabs' => $tabs,
        ];
    }

    private function getComponentDefinitions(): array
    {
        return [
            'list' => [
                'pullToRefresh' => true,
                'infiniteScroll' => true,
                'searchable' => true,
                'swipeActions' => true,
            ],
            'form' => [
                'validation' => true,
                'submitLabel' => 'Save',
                'cancelLabel' => 'Cancel',
            ],
            'detail' => [
                'editable' => true,
            ],
            'kanban' => [
                'swipeable' => true,
                'dragDrop' => true,
            ],
            'stats-row' => [
                'columns' => 2,
                'refreshable' => true,
            ],
        ];
    }
}
