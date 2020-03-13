<?php


namespace App\Console\Schedules\Learning;


use App\Console\Schedules\BaseSchedule;

class ExportBookLearningProcess extends BaseSchedule
{
    public function handle()
    {
        \DB::setPdo($this->getConnPdo('learning', 'online'));

        // 图书ids
        $sql = <<<EOF
SELECT
GROUP_CONCAT(  distinct course_book.id  ) book_ids
FROM
    `learning`.`course_book`
    INNER JOIN `label_scope_map` ON `course_book`.`label_id` = `label_scope_map`.`label_id`
    left join course_book_catalog on course_book_catalog.book_id = course_book.id and course_book_catalog.deleted_at is null
    left join assessment on course_book_catalog.id = assessment.catalog_id AND assessment.type_id = 5 and assessment.deleted_at is null
WHERE
    course_book.`subject_id` = '7' and course_book.deleted_at is null
    AND `label_scope_map`.`scope_id` = "1"
    AND `label_scope_map`.`is_available` = "1"
    AND `label_scope_map`.`is_visible` = "1"
        and course_book_catalog.is_available = 0
EOF;

        $book_ids = \DB::select(\DB::raw($sql));
        $book_ids = $book_ids[0]->book_ids;


        // 订阅人数
        $sql = <<<EOF
    select tmp.* ,count(*) 'unit_total' ,
    sum(course_book_catalog.is_available) 'up_unit_total' , 
    GROUP_CONCAT(if(course_book_catalog.is_available = 1, course_book_catalog.`name`, ''))  'up_unit_name' from  (
SELECT
    course_book.id book_id,
    course_book.name book_name,
    count(course_user_book_record.id) student_count    
FROM
    `learning`.`course_book`
    left join course_user_book_record on course_user_book_record.book_id = course_book.id
WHERE
    course_book.`id` IN (
$book_ids
)
    AND deleted_at IS NULL
    GROUP BY course_book.id
    ) tmp
    left join course_book_catalog on tmp.book_id = course_book_catalog.book_id and course_book_catalog.deleted_at is null
    GROUP BY tmp.book_id
EOF;

        $student_count = \DB::select(\DB::raw($sql));

 // 学习进度
        $sql = <<<EOF
SELECT
    book_id,  CONCAT(course_book_catalog.name,'(',count(*),')' ) unit
FROM
    `learning`.`course_student_overview`
    left join course_book_catalog on course_book_catalog.id = course_student_overview.object_id
    where book_id IN (
$book_ids
    )
    group by object_id
    ORDER BY book_id
EOF;

        $process = \DB::select(\DB::raw($sql));
        $process = json_decode(json_encode($process), true);
        $processlist = ( collect($process)->groupBy('book_id')->map(function ($book){
            return $book->sortBy('unit')->pluck('unit')->toArray();

        }));

        $report1 = [];
        $report1[] = ['图书id', '图书名称（上架未完成的课程名称）', '图书所含单元', '上架单元','上架单元名称', '已完成单元', '已完成单元详情', '订阅人数'];


        foreach ($student_count as $item){
            $book_id = $item->book_id;
            $book_name = $item->book_name;
            $student_count = $item->student_count;      // 订阅学生
            $unit_total = $item->unit_total;            // 所含单元
            $up_unit_total = $item->up_unit_total;      // 上架单元
            $up_unit_name = $item->up_unit_name;      // 上架单元名称

            $up_unit_name = implode(',', array_filter(explode(',',$up_unit_name)));

            if (isset($processlist[$book_id])){
                $count = count($processlist[$book_id]);
                foreach ($processlist[$book_id] as $key=>$process_item){
                    if (!$key){
                        $report1[] = [
                            $book_id,
                            $book_name,
                            $unit_total,
                            $up_unit_total,
                            $up_unit_name,
                            $count,
                            $process_item,
                            $student_count
                        ];
                    }else{
                        $report1[] = [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $process_item,
                            ''
                        ];
                    }
                }


            }else{
                $report1[] = [
                    $book_id,
                    $book_name,
                    $unit_total,
                    $up_unit_total,
                    $up_unit_name,
                    0,
                    '/',
                    $student_count
                ];

            }


        }

#####################################################期末冲刺

        // 图书ids
        $sql = <<<EOF
SELECT
GROUP_CONCAT(  distinct course_book.id  ) book_ids
FROM
    `learning`.`course_book`
    INNER JOIN `label_scope_map` ON `course_book`.`label_id` = `label_scope_map`.`label_id`
    left join course_book_catalog on course_book_catalog.book_id = course_book.id and course_book_catalog.deleted_at is null
    left join assessment on course_book_catalog.id = assessment.catalog_id AND assessment.type_id = 5 and assessment.deleted_at is null
WHERE
    course_book.`subject_id` = '29' and course_book.deleted_at is null
    AND `label_scope_map`.`scope_id` = "1"
    AND `label_scope_map`.`is_available` = "1"
    AND `label_scope_map`.`is_visible` = "1"
        and course_book_catalog.is_available = 0
EOF;

        $book_ids = \DB::select(\DB::raw($sql));
        $book_ids = $book_ids[0]->book_ids;


        // 订阅人数
        $sql = <<<EOF
     select tmp.* ,count(*) 'unit_total' ,
    sum(course_book_catalog.is_available) 'up_unit_total' , 
    GROUP_CONCAT(if(course_book_catalog.is_available = 1, course_book_catalog.`name`, ''))  'up_unit_name' from   (
SELECT
    course_book.id book_id,
    course_book.name book_name,
    count(course_user_book_record.id) student_count    
FROM
    `learning`.`course_book`
    left join course_user_book_record on course_user_book_record.book_id = course_book.id
WHERE
    course_book.`id` IN (
$book_ids
)
    AND deleted_at IS NULL
    GROUP BY course_book.id
    ) tmp
    left join course_book_catalog on tmp.book_id = course_book_catalog.book_id and course_book_catalog.deleted_at is null
    GROUP BY tmp.book_id
EOF;

        $student_count = \DB::select(\DB::raw($sql));

        // 学习进度
        $sql = <<<EOF
SELECT
    book_id,  CONCAT(course_book_catalog.name,'(',count(*),')' ) unit
FROM
    `learning`.`course_student_overview`
    left join course_book_catalog on course_book_catalog.id = course_student_overview.object_id
    where book_id IN (
$book_ids
    )
    group by object_id
    ORDER BY book_id
EOF;

        $process = \DB::select(\DB::raw($sql));
        $process = json_decode(json_encode($process), true);
        $processlist = ( collect($process)->groupBy('book_id')->map(function ($book){
            return $book->sortBy('unit')->pluck('unit')->toArray();

        }));
        $report2 = [];
        $report2[] = ['图书id', '图书名称（上架未完成的课程名称）', '图书所含单元', '上架单元', '上架单元名称', '已完成单元', '已完成单元详情', '订阅人数'];


        foreach ($student_count as $item){
            $book_id = $item->book_id;
            $book_name = $item->book_name;
            $student_count = $item->student_count;      // 订阅学生
            $unit_total = $item->unit_total;            // 所含单元
            $up_unit_total = $item->up_unit_total;      // 上架单元
            $up_unit_name = $item->up_unit_name;      // 上架单元名称
            $up_unit_name = implode(',', array_filter(explode(',',$up_unit_name)));
            if (isset($processlist[$book_id])){
                $count = count($processlist[$book_id]);
                foreach ($processlist[$book_id] as $key=>$process_item){
                    if (!$key){
                        $report2[] = [
                            $book_id,
                            $book_name,
                            $unit_total,
                            $up_unit_total,
                            $up_unit_name,
                            $count,
                            $process_item,
                            $student_count
                        ];
                    }else{
                        $report2[] = [
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                            $process_item,
                            ''
                        ];
                    }
                }


            }else{
                $report2[] = [
                    $book_id,
                    $book_name,
                    $unit_total,
                    $up_unit_total,
                    $up_unit_name,
                    0,
                    '/',
                    $student_count
                ];

            }


        }
        $filename = 'BXG_book_learn_process' . date('md');
        $path = 'learning';
        $file = $this->sheetsStore($path . '/' . $filename, ['背课文'=>$report1, '期末冲刺'=>$report2]);
        $this->email(['1321298491@qq.com','zhanxuewen2018@126.com'],
            'emails.export2', ['object' => '每日' . '学习进度'],
            '百项过未上架图书学习进度_'.date('md'), realpath($file));
    }

}