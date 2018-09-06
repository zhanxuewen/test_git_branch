<?php

namespace App\Console\Commands\Summary;

use Carbon\Carbon;
use App\Helper\SummaryHelper;
use Illuminate\Console\Command;

class ExportSchoolSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:school:summary';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';
    
    protected $plat = [2 => '家长端', 5 => '学生端', 6 => '优惠页'];
    protected $app = [1 => '微信X', 2 => '支付宝', 3 => 'IOS'];
    protected $card_type = ['normal' => '整卡', 'month' => '按月', 'day' => '按天'];
    protected $helper;
    protected $marketers;
    protected $set_prices;
    protected $date;
    protected $cont_s;
    protected $regions;
    protected $parts;
    
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->helper     = new SummaryHelper();
        $this->marketers  = $this->helper->getManagers();
        $this->set_prices = $this->helper->setPrices();
        $this->date       = $this->helper->getContractDate();
        $this->cont_s     = $this->helper->getContract();
        $this->regions    = $this->helper->getRegions();
        $this->parts      = $this->helper->getParts();
    }
    
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $start   = Carbon::today()->subMonth()->startOfMonth();
        $end     = Carbon::today()->subMonth()->endOfMonth()->endOfDay();
        $reports = [
            '月活表' => $this->getActivity($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')),
            '月线上交费明细' => $this->getSchoolOrder($start, $end),
            '月学校代交明细' => $this->getSchoolOffline($start, $end),
        ];
        $this->export($start->format('YmdHis').'_'.$end->format('YmdHis').'_Summary', $reports);
    }
    
    protected function getActivity($start, $end)
    {
        $this->info('Get Activity');
        $between  = ['start' => $start, 'end' => $end];
        $pop      = $this->helper->getPopExpire();
        $tea_cou  = $this->helper->getSchoolTeacher();
        $tea_new  = $this->helper->getSchoolNewTeacher($between);
        $tea_act  = $this->helper->getSchoolActTeacher($between);
        $stu_cou  = $this->helper->getSchoolStudent();
        $stu_new  = $this->helper->getSchoolNewStudent($between);
        $stu_act  = $this->helper->getSchoolActStudent($between);
        $stu_try  = $this->helper->getSchoolTrail($end);
        $stu_eff  = $this->helper->getSchoolEffect($end);
        $star     = $this->helper->getSchoolStar($between);
        $score    = $this->helper->getSchoolScore($between);
        $schools  = \DB::setPdo(app('online_pdo'))->table('school')->where('is_active', 1)->get();
        $report   = [];
        $report[] = ['学校ID', '合同档', '学校名称', '学校试用期', '市场专员', '省', '市', '区县', '签约日期', '加盟校', '教师数', '新增教师', '活跃教师', '学生数', '新增学生', '活跃学生', '有效期内学生数', '试用期内学生数', '本月星星', '本月积分'];
        $this->output->progressStart(count($schools));
        foreach ($schools as $school) {
            $s_id     = $school->id;
            $region   = is_null($s_id) ? null : explode('/', $this->regions[$s_id]);
            $report[] = [
                'id' => $s_id,
                'title' => isset($this->cont_s[$s_id]) ? $this->cont_s[$s_id] : null,
                'name' => $school->name,
                'pop' => isset($pop[$s_id]) ? $pop[$s_id] : null,
                'marketer' => $school->marketer_id == 0 ? null : $this->marketers[$school->marketer_id],
                'shn' => isset($region[0]) ? $region[0] : null,
                'shi' => isset($region[1]) ? $region[1] : null,
                'qu' => isset($region[2]) ? $region[2] : null,
                'date' => isset($this->date[$s_id]) ? $this->date[$s_id] : null,
                'part' => isset($this->parts[$s_id]) ? $this->parts[$s_id] : null,
                't_cou' => isset($tea_cou[$s_id]) ? $tea_cou[$s_id] : 0,
                't_new' => isset($tea_new[$s_id]) ? $tea_new[$s_id] : 0,
                't_act' => isset($tea_act[$s_id]) ? $tea_act[$s_id] : 0,
                's_cou' => isset($stu_cou[$s_id]) ? $stu_cou[$s_id] : 0,
                's_new' => isset($stu_new[$s_id]) ? $stu_new[$s_id] : 0,
                's_act' => isset($stu_act[$s_id]) ? $stu_act[$s_id] : 0,
                's_eff' => isset($stu_eff[$s_id]) ? $stu_eff[$s_id] : 0,
                's_try' => isset($stu_try[$s_id]) ? $stu_try[$s_id] : 0,
                'star' => isset($star[$s_id]) ? $star[$s_id] : 0,
                'score' => isset($score[$s_id]) ? $score[$s_id] : 0,
            ];
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        return $report;
    }
    
    protected function getSchoolOrder($start, $end)
    {
        $this->info('Get Order');
        $report   = [];
        $report[] = ['订单日期', '订单时间', '支付通道', '学校ID', '合同档', '学校名称', '省', '市', '区县', '市场专员', '加盟校', '协议价', '金额', '拼团', '卡类别', '人数', '结算额', '支付方式', '昵称', '备注名', '所在班级', '学生手机', '退款时间'];
        foreach (['order', 'order_refund'] as $type) {
            $orders = $this->queryOrder($start, $end, $type);
            $this->output->progressStart(count($orders));
            foreach ($orders as $order) {
                $report[] = $this->getOrderData($order, $type == 'order' ? true : false);
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
        return $report;
    }
    
    protected function getSchoolOffline($start, $end)
    {
        $this->info('Get Offline');
        $report   = [];
        $report[] = ['订单日期', '订单时间', '支付通道', '学校ID', '合同档', '学校名称', '省', '市', '区县', '市场专员', '加盟校', '协议价', '金额', '天数', '卡类型', '人数', '结算额', '退款时间', '昵称', '备注名', '所在班级', '学生手机'];
        foreach (['order_offline', 'order_offline_refund'] as $type) {
            $orders = $this->queryOffline($start, $end, $type);
            $this->output->progressStart(count($orders));
            foreach ($orders as $order) {
                $report[] = $this->getOfflineData($order, $type == 'order_offline' ? true : false);
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
        return $report;
    }
    
    protected function queryOrder($start, $end, $type)
    {
        $same   = 'trade_type, is_group_order, school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, group_concat(DISTINCT vanclass.name) as vanclass_name, user.phone, commodity_name, commodity_id';
        $select = [
            'order' => $same.', out_trade_no, refunded_at, pay_fee',
            'order_refund' => $same.', order_refund.out_refund_no, order.out_trade_no, order_refund.created_at, refund_fee'
        ];
        $query  = \DB::setPdo(app('online_pdo'))->table($type)
            ->selectRaw($select[$type]);
        $type == 'order_refund' ? $query->join('order', 'order.out_trade_no', '=', 'order_refund.out_trade_no') : null;
        $query->join('user_account', 'user_account.id', '=', 'order.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order.school_id', 'left')
            ->join('vanclass_student', 'order.student_id', '=', 'vanclass_student.student_id', 'left')
            ->join('vanclass', 'vanclass.id', '=', 'vanclass_student.vanclass_id', 'left')
            ->whereBetween($type.'.created_at', [$start, $end]);
        $type == 'order' ? $query->whereNotNull('order.transaction_id') : null;
        return $query->groupBy('order.id')->get();
    }
    
    protected function queryOffline($start, $end, $type)
    {
        $same   = 'school.id, school.name, school.marketer_id, nickname, group_concat(DISTINCT vanclass_student.mark_name) as _mark_name, group_concat(DISTINCT vanclass.name) as vanclass_name, user.phone';
        $select = [
            'order_offline' => $same.', order_offline.created_at, pay_fee, days, date_type, refunded_at',
            'order_offline_refund' => $same.', order_offline_refund.created_at, pay_fee, refund_fee, refund_days'
        ];
        $query  = \DB::setPdo(app('online_pdo'))->table($type)
            ->selectRaw($select[$type]);
        $type == 'order_offline_refund' ? $query->join('order_offline', 'order_offline_refund.offline_id', '=', 'order_offline.id') : null;
        $query->join('user_account', 'user_account.id', '=', 'order_offline.student_id')
            ->join('user', 'user.id', '=', 'user_account.user_id')
            ->join('school', 'school.id', '=', 'order_offline.school_id')
            ->join('vanclass_student', 'order_offline.student_id', '=', 'vanclass_student.student_id', 'left')
            ->join('vanclass', 'vanclass.id', '=', 'vanclass_student.vanclass_id', 'left')
            ->whereBetween($type.'.created_at', [$start, $end]);
        return $query->groupBy('order_offline.id')->get();
    }
    
    protected function beforeData($order, $is_order)
    {
        $s_id   = $order->id;
        $num    = $is_order ? $order->out_trade_no : $order->out_refund_no;
        $time   = $num == '' && $is_order == false ? Carbon::parse($order->created_at) : Carbon::parse(substr($num, 0, 14));
        $type   = explode('_', substr($is_order ? $num : $order->out_trade_no, 15, 5));
        $region = is_null($s_id) ? null : explode('/', $this->regions[$s_id]);
        if ($type[2] != 1) {
            $get_type = $this->app[$type[2]];
        } else {
            $get_type = $type[0] == 5 ? '微信6' : '微信1';
        }
        $set_price = isset($this->cont_s[$s_id])
            ? $this->set_prices[$order->commodity_id][$this->cont_s[$s_id]]
            : $this->set_prices[$order->commodity_id]['F'];
        return [$s_id, $time, $set_price, $get_type, $type, $region];
    }
    
    protected function getOrderData($order, $is_order)
    {
        list($s_id, $time, $set_price, $get_type, $type, $region) = $this->beforeData($order, $is_order);
        $data = [
            'date' => $time->format('Y-m-d'),
            'time' => $time->format('H:i:s'),
            'channel' => $this->plat[$type[0]],
            'id' => $s_id,
            'title' => isset($this->cont_s[$s_id]) ? $this->cont_s[$s_id] : null,
            'name' => $order->name,
            'shn' => isset($region[0]) ? $region[0] : null,
            'shi' => isset($region[1]) ? $region[1] : null,
            'qu' => isset($region[2]) ? $region[2] : null,
            'marketer' => is_null($order->marketer_id) ? null : $this->marketers[$order->marketer_id],
            'part' => isset($this->parts[$s_id]) ? $this->parts[$s_id] : null,
            'set_price' => $set_price,
            'fee' => $is_order ? $order->pay_fee : -$order->refund_fee,
            'group' => $order->is_group_order,
            'comm' => $order->commodity_name,
            'count' => $is_order ? 1 : -1,
            'sum' => $is_order ? $set_price : -$set_price,
            'type' => $get_type,
            'nickname' => $order->nickname,
            'mark_name' => $order->_mark_name,
            'van_name' => $order->vanclass_name,
            'phone' => substr_replace($order->phone, '****', 3, 4),
            'refund' => $is_order ? $order->refunded_at : null,
        ];
        return $data;
    }
    
    protected function getOfflineData($order, $is_offline)
    {
        $time   = Carbon::parse($order->created_at);
        $s_id   = $order->id;
        $region = is_null($s_id) ? null : explode('/', $this->regions[$s_id]);
        $fee    = $order->pay_fee;
        $r_fee  = $is_offline ? null : $order->refund_fee;
        $data   = [
            'date' => $time->format('Y-m-d'),
            'time' => $time->format('H:i:s'),
            'channel' => '学校代交',
            'id' => $s_id,
            'title' => isset($this->cont_s[$s_id]) ? $this->cont_s[$s_id] : null,
            'name' => $order->name,
            'shn' => isset($region[0]) ? $region[0] : null,
            'shi' => isset($region[1]) ? $region[1] : null,
            'qu' => isset($region[2]) ? $region[2] : null,
            'marketer' => is_null($order->marketer_id) ? null : $this->marketers[$order->marketer_id],
            'part' => isset($this->parts[$s_id]) ? $this->parts[$s_id] : null,
            'set_price' => $fee,
            'fee' => $is_offline ? $fee : -$r_fee,
            'days' => $is_offline ? $order->days : $order->refund_days,
            'card' => $is_offline ? $this->card_type[$order->date_type] : '按天',
            'count' => $is_offline ? 1 : -1,
            'sum' => $is_offline ? $fee : -$r_fee,
            'refund' => $is_offline ? $order->refunded_at : null,
            'nickname' => $order->nickname,
            'mark_name' => $order->_mark_name,
            'van_name' => $order->vanclass_name,
            'phone' => substr_replace($order->phone, '****', 3, 4),
        ];
        return $data;
    }
    
    protected function export($file, $reports)
    {
        $path = storage_path('exports').'/summary';
        \Excel::create($file, function ($Excel) use ($reports) {
            foreach ($reports as $table => $report) {
                $Excel->sheet($table, function ($sheet) use ($report) {
                    $sheet->rows($report);
                });
            }
        })->store('xls', $path);
    }
}
