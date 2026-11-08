<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $today = now()->toImmutable()->startOfDay();

        $toRules = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('from')) {
            $toRules[] = 'after_or_equal:from';
        }

        $validator = Validator::make($request->query(), [
            'group' => ['nullable', Rule::in([ReportService::GROUP_DAY, ReportService::GROUP_MONTH])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => $toRules,
        ], [], [
            'group' => 'Pengelompokan',
            'from' => 'Tanggal mulai',
            'to' => 'Tanggal akhir',
        ]);

        $errors = $validator->errors();
        $group = ReportService::GROUP_DAY;
        $from = $today->startOfMonth();
        $to = $today;

        if (! $validator->fails()) {
            $group = $request->query('group') === ReportService::GROUP_MONTH
                ? ReportService::GROUP_MONTH
                : ReportService::GROUP_DAY;

            $candidateFrom = $request->filled('from')
                ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->query('from'))
                : $from;
            $candidateTo = $request->filled('to')
                ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->query('to'))
                : $to;

            if ($candidateFrom->gt($candidateTo)) {
                $errors->add('to', 'Tanggal akhir harus sama dengan atau setelah Tanggal mulai.');
            } elseif ($candidateFrom->diffInDays($candidateTo) > 365) {
                $errors->add('to', 'Rentang tanggal maksimal 366 hari.');
            } else {
                $from = $candidateFrom;
                $to = $candidateTo;
            }
        }

        $startOfThisMonth = $today->startOfMonth();
        $lastMonth = $startOfThisMonth->subMonth();

        $presets = [
            ['Hari ini', ['from' => $today->toDateString(), 'to' => $today->toDateString(), 'group' => 'day']],
            ['7 hari terakhir', ['from' => $today->subDays(6)->toDateString(), 'to' => $today->toDateString(), 'group' => 'day']],
            ['Bulan ini', ['from' => $startOfThisMonth->toDateString(), 'to' => $today->toDateString(), 'group' => 'day']],
            ['Bulan lalu', ['from' => $lastMonth->toDateString(), 'to' => $lastMonth->endOfMonth()->toDateString(), 'group' => 'day']],
            ['12 bulan terakhir', ['from' => $startOfThisMonth->subMonths(11)->toDateString(), 'to' => $today->toDateString(), 'group' => 'month']],
        ];

        return view('admin.reports.index', [
            'report' => $this->reports->build($from, $to, $group),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'group' => $group],
            'filterErrors' => $errors,
            'presets' => $presets,
        ]);
    }
}
