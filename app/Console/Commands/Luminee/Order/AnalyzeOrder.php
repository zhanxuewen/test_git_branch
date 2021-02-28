<?php

namespace App\Console\Commands\Luminee\Order;

use DB;
use App\Foundation\PdoBuilder;
use Illuminate\Console\Command;

class AnalyzeOrder extends Command
{
    use PdoBuilder;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $table = [];
    protected $query;
    protected $date;
    protected $groupBy;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        DB::setPdo($this->getConnPdo('core', 'online'));
        $this->date = '2019-06-01';
        $this->groupBy = 'order.commodity_id';

        $title = ['', '', 'Order', 'Offline', 'Units', 'Extra'];
        $this->group1();
        $this->group1_1();
        $this->group1_2();
        $this->group1_3();
        $this->group2();
        $this->group2_1();
        $this->group2_2();
        $this->group2_3();


        $this->table($title, $this->table);
    }

    protected function group1()
    {
        $this->table[] = ['1. 未退款', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc', false)->first();
        $data2 = $this->build('of', 'dsc', false)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'A=C+E'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'B=D#F'];
        $this->table[] = ['', '总金额', $data1['suu'], $data2['suu'], '元', 'V=W+X'];
    }

    protected function group1_1()
    {
        $this->table[] = ['1). 正常卡', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc', false)->filterCard(true)->first();
        $data2 = $this->build('of', 'dsc', false)->filterDays(true)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'C=G+H+I+J'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'D'];
        $this->table[] = ['', '总金额', $data1['suu'], $data2['suu'], '元', 'W'];
    }

    protected function group1_2()
    {
        $this->table[] = ['2). 感恩节单词卡', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc', false)->filterCard(false)->first();
        $data2 = $this->build('of', 'dsc', false)->filterDays(false)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'E'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'F'];
        $this->table[] = ['', '总金额', $data1['suu'], $data2['suu'], '元', 'X'];
    }

    protected function group1_3()
    {
        $this->table[] = ['3). 卡种', '', '', '', '', ''];
        $data1 = $this->build('or', 'g', false)->filterCard(true)->groupBy(true)->getGroup('commodity_id');
        $data2 = $this->build('of', 'y', false)->filterDays(true)->groupBy(false)->getGroup('days');
        $this->table[] = ['', '月卡', $data1[1], $data2[31], '张', 'G'];
        $this->table[] = ['', '季卡', $data1[2], $data2[92], '张', 'H'];
        $this->table[] = ['', '半年卡', $data1[3], $data2[183], '张', 'I'];
        $this->table[] = ['', '年卡', $data1[4], $data2[365], '张', 'J'];
    }

    protected function group2()
    {
        $this->table[] = ['2. 退款项', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc', true)->first();
        $data2 = $this->build('of', 'dsc', true)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'K=N+S'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'L=O#T'];
        $this->table[] = ['', '涉及金额', $data1['suu'], $data2['suu'], '元', 'M=P+U'];
    }

    protected function group2_1()
    {
        $this->table[] = ['1). 卡种', '', '', '', '', ''];
        $data1 = $this->build('or', 'g', true)->filterCard(true)->groupBy(true)->getGroup('commodity_id');
        $data2 = $this->build('of', 'y', true)->filterDays(true)->groupBy(false)->getGroup('days');
        $this->table[] = ['', '月卡', $data1[1], $data2[31], '张', ''];
        $this->table[] = ['', '季卡', $data1[2], $data2[92], '张', ''];
        $this->table[] = ['', '半年卡', $data1[3], $data2[183], '张', ''];
        $this->table[] = ['', '年卡', $data1[4], $data2[365], '张', ''];
    }

    protected function group2_2()
    {
        $this->table[] = ['2). 部分退', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc-pr', true)->whereRefund(true)->first();
        $data2 = $this->build('of', 'dsc-pr', true)->whereRefund(true)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'N'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'O'];
        $this->table[] = ['', '涉及金额', $data1['suu'], $data2['suu'], '元', 'P=Q+R'];
        $this->table[] = ['', '收入金额', $data1['par'], $data2['par'], '元', 'Q'];
        $this->table[] = ['', '退款金额', $data1['rff'], $data2['rff'], '元', 'R'];
    }

    protected function group2_3()
    {
        $this->table[] = ['3). 全部退', '', '', '', '', ''];
        $data1 = $this->build('or', 'dsc', true)->whereRefund(false)->first();
        $data2 = $this->build('of', 'dsc', true)->whereRefund(false)->first();
        $this->table[] = ['', '订单数', $data1['coo'], $data2['coo'], '笔', 'S'];
        $this->table[] = ['', '学生数', $data1['stu'], $data2['stu'], '人', 'T'];
        $this->table[] = ['', '涉及金额', $data1['suu'], $data2['suu'], '元', 'U'];
    }

    protected function build($table, $select, $with_r)
    {
        $tables = ['or' => 'order', 'of' => 'order_offline'];
        $t = $tables[$table];
        $refund = ['or' => 'order_refund', 'of' => 'order_offline_refund'];
        $r = $refund[$table];
        $s = $with_r ? '%refund%' : '%success%';
        $this->query = DB::table($t)->where($t . '.pay_status', 'like', $s)->whereNull($t . '.deleted_at');
        $this->select($select)->whereDate($with_r ? $r : $t, $with_r ? 'success_at' : 'finished_at');
        if ($with_r) {
            $one = ['or' => 'out_trade_no', 'of' => 'id'];
            $two = ['or' => 'out_trade_no', 'of' => 'offline_id'];
            $this->joinR($t, $r, $one[$table], $two[$table]);
        }
        return $this;
    }

    protected function joinR($table, $join, $one, $two)
    {
        $this->query = $this->query->join($join, $table . '.' . $one, '=', $join . '.' . $two);
        return $this;
    }

    protected function select($select)
    {
        $raw = [];
        strstr($select, 'd') ? $raw[] = 'count(DISTINCT student_id) as stu' : null;
        strstr($select, 's') ? $raw[] = 'sum(pay_fee) as suu' : null;
        strstr($select, '-p') ? $raw[] = 'sum(pay_fee - refund_fee) as par' : null;
        strstr($select, 'r') ? $raw[] = 'sum(refund_fee) as rff' : null;
        strstr($select, 'c') ? $raw[] = 'count(*) as coo' : null;
        strstr($select, 'g') ? $raw[] = 'count(*) as coo, order.commodity_id' : null;
        strstr($select, 'y') ? $raw[] = 'count(*) as coo, order_offline.days' : null;
        $this->query = $this->query->selectRaw(implode(',', $raw));
        return $this;
    }

    protected function whereDate($table, $at)
    {
        $this->query = $this->query->where($table . '.' . $at, '<', $this->date);
        return $this;
    }

    protected function whereRefund($part)
    {
        $this->query = $this->query->whereRaw('pay_fee ' . ($part ? '<>' : '=') . ' refund_fee');
        return $this;
    }

    protected function filterCard($normal)
    {
        if ($normal) {
            $this->query = $this->query->where($this->groupBy, '<', 5);
        } else {
            $this->query = $this->query->where($this->groupBy, 7);
        }
        return $this;
    }

    protected function filterDays($normal)
    {
        if ($normal) {
            $this->query = $this->query->whereIn('order_offline.days', [31, 92, 183, 365]);
        } else {
            $this->query = $this->query->whereNotIn('order_offline.days', [31, 92, 183, 365]);
        }
        return $this;
    }

    protected function groupBy($order)
    {
        $groupBy = $order ? $this->groupBy : 'order_offline.days';
        $this->query = $this->query->groupBy($groupBy);
        return $this;
    }

    protected function get()
    {
        return json_decode(json_encode($this->query->get()), true);
    }

    protected function first()
    {
        return $this->get()[0];
    }

    protected function getGroup($group)
    {
        $data = [];
        foreach ($this->query->get() as $item) {
            $data[$item->$group] = $item->coo;
        }
        return $data;
    }

}
