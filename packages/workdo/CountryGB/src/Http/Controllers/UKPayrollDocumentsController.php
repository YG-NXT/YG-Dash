<?php

namespace Workdo\CountryGB\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Workdo\Hrm\Models\PayrollEntry;
use App\Models\User;

class UKPayrollDocumentsController extends Controller
{
    public function p45(PayrollEntry $payrollEntry)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $payrollEntry->load(['employee.user', 'employee.designation', 'payroll']);
        $employee = $payrollEntry->employee;
        $company = User::where('id', $employee->created_by)->first();

        return view('CountryGB::documents.p45', [
            'payrollEntry' => $payrollEntry,
            'employee' => $employee,
            'company' => $company,
            'payroll' => $payrollEntry->payroll,
            'leavingDate' => $payrollEntry->payroll->pay_period_end,
            'payToDate' => $payrollEntry->gross_pay,
            'taxToDate' => $payrollEntry->total_deductions,
            'taxCode' => '1257L',
        ]);
    }

    public function p60(PayrollEntry $payrollEntry)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $payrollEntry->load(['employee.user', 'employee.designation', 'payroll']);
        $employee = $payrollEntry->employee;
        $company = User::where('id', $employee->created_by)->first();

        // In a real implementation, sum all payroll entries for the tax year
        $totalPay = $payrollEntry->gross_pay;
        $totalTax = $payrollEntry->total_deductions;
        $totalNI = $payrollEntry->total_deductions * 0.12; // Simplified NI calc

        return view('CountryGB::documents.p60', [
            'payrollEntry' => $payrollEntry,
            'employee' => $employee,
            'company' => $company,
            'taxYear' => date('Y') . '/' . (date('Y') + 1),
            'taxCode' => '1257L',
            'totalPay' => $totalPay,
            'totalTax' => $totalTax,
            'totalNI' => $totalNI,
        ]);
    }

    public function p11d(PayrollEntry $payrollEntry)
    {
        if (!Auth::user()->can('view-payrolls')) {
            return redirect()->back()->with('error', __('Permission denied'));
        }

        $payrollEntry->load(['employee.user', 'employee.designation', 'payroll']);
        $employee = $payrollEntry->employee;
        $company = User::where('id', $employee->created_by)->first();

        // Benefits would come from employee benefits/allowances
        $benefits = collect([
            ['name' => 'Private medical insurance', 'amount' => 1200],
            ['name' => 'Company car', 'amount' => 3500],
            ['name' => 'Other benefits', 'amount' => 500],
        ]);

        return view('CountryGB::documents.p11d', [
            'payrollEntry' => $payrollEntry,
            'employee' => $employee,
            'company' => $company,
            'benefits' => $benefits,
            'taxYear' => date('Y') . '/' . (date('Y') + 1),
        ]);
    }
}
