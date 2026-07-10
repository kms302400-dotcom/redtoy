<?php
$sub_menu = '102840';
include_once('./_common.php');

// 상품이 많을 경우 대비 설정변경
set_time_limit ( 0 );
ini_set('memory_limit', '50M');

auth_check_menu($auth, $sub_menu, 'w');

$sch_cp_ix = 1;

function only_number($n)
{
    return preg_replace('/[^0-9]/', '', $n);
}

if($_FILES['excelfile']['tmp_name']) {
    $file = $_FILES['excelfile']['tmp_name'];

    include_once(G5_LIB_PATH.'/Excel/reader.php');

    $data = new Spreadsheet_Excel_Reader();

    // Set output Encoding.
    $data->setOutputEncoding('UTF-8');

    /***
    * if you want you can change 'iconv' to mb_convert_encoding:
    * $data->setUTFEncoder('mb');
    *
    **/

    /***
    * By default rows & cols indeces start with 1
    * For change initial index use:
    * $data->setRowColOffset(0);
    *
    **/



    /***
    *  Some function for formatting output.
    * $data->setDefaultFormat('%.2f');
    * setDefaultFormat - set format for columns with unknown formatting
    *
    * $data->setColumnFormat(4, '%.3f');
    * setColumnFormat - set format for column (apply only to number fields)
    *
    **/

    $data->read($file);

    /*


     $data->sheets[0]['numRows'] - count rows
     $data->sheets[0]['numCols'] - count columns
     $data->sheets[0]['cells'][$i][$j] - data from $i-row $j-column

     $data->sheets[0]['cellsInfo'][$i][$j] - extended info about cell

        $data->sheets[0]['cellsInfo'][$i][$j]['type'] = "date" | "number" | "unknown"
            if 'type' == "unknown" - use 'raw' value, because  cell contain value with format '0.00';
        $data->sheets[0]['cellsInfo'][$i][$j]['raw'] = value if cell without format
        $data->sheets[0]['cellsInfo'][$i][$j]['colspan']
        $data->sheets[0]['cellsInfo'][$i][$j]['rowspan']
    */

    error_reporting(E_ALL ^ E_NOTICE);

    $dup_it_id = array();
    $fail_it_id = array();
    $dup_count = 0;
    $total_count = 0;
    $fail_count = 0;
    $succ_count = 0;

    for ($i = 6; $i <= $data->sheets[0]['numRows']; $i++) {
        $total_count++;

        $j = 1;

        $xlsx01 = addslashes($data->sheets[0]['cells'][$i][$j++]); // 플러스친구 아이디
        $at_tmplId = addslashes($data->sheets[0]['cells'][$i][$j++]);
        $at_title = addslashes($data->sheets[0]['cells'][$i][$j++]);
        $at_type = addslashes($data->sheets[0]['cells'][$i][$j++]);
        $at_msg = addslashes($data->sheets[0]['cells'][$i][$j++]);
        $xlsx01 = addslashes($data->sheets[0]['cells'][$i][$j++]); // 부가정보 (최대 500자)
        $xlsx02 = addslashes($data->sheets[0]['cells'][$i][$j++]); // 광고성메시지(최대 80자)
        $xlsx03 = addslashes($data->sheets[0]['cells'][$i][$j++]); // 보안 템플릿 여부 (true:설정, false:미설정)
        $xlsx04 = addslashes($data->sheets[0]['cells'][$i][$j++]); // 템플릿 카테고리코드

        $buttons = array();
        $buttons[0]['type'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼타입
        $buttons[0]['name'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼명(최대 14자)
        $buttons[0]['url_mobile'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 모바일링크 / 플러그인ID
        $buttons[0]['url_pc'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // PC링크
        $buttons[0]['scheme_android'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // android스킴
        $buttons[0]['scheme_ios'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // ios스킴

        $buttons[1]['type'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼타입
        $buttons[1]['name'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼명(최대 14자)
        $buttons[1]['url_mobile'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 모바일링크 / 플러그인ID
        $buttons[1]['url_pc'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // PC링크
        $buttons[1]['scheme_android'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // android스킴
        $buttons[1]['scheme_ios'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // ios스킴

        $buttons[2]['type'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼타입
        $buttons[2]['name'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼명(최대 14자)
        $buttons[2]['url_mobile'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 모바일링크 / 플러그인ID
        $buttons[2]['url_pc'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // PC링크
        $buttons[2]['scheme_android'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // android스킴
        $buttons[2]['scheme_ios'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // ios스킴

        $buttons[3]['type'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼타입
        $buttons[3]['name'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼명(최대 14자)
        $buttons[3]['url_mobile'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 모바일링크 / 플러그인ID
        $buttons[3]['url_pc'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // PC링크
        $buttons[3]['scheme_android'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // android스킴
        $buttons[3]['scheme_ios'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // ios스킴

        $buttons[4]['type'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼타입
        $buttons[4]['name'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 버튼명(최대 14자)
        $buttons[4]['url_mobile'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // 모바일링크 / 플러그인ID
        $buttons[4]['url_pc'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // PC링크
        $buttons[4]['scheme_android'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // android스킴
        $buttons[4]['scheme_ios'] = addslashes($data->sheets[0]['cells'][$i][$j++]); // ios스킴

        $query = "select at_id from {$g5['wz_alimtalk_template_table']} where at_tmplId = '".$at_tmplId."'";
        $at = sql_fetch($query);

		if(!$at_tmplId || $at['at_id']) {
            $fail_count++;
            continue;
        }

        // 알림톡템플릿
        $query = " insert into {$g5['wz_alimtalk_template_table']}
                     set at_tmplId = '".$at_tmplId."',
                         at_title = '".$at_title."',
                         at_type = '".$at_type."',
                         at_msg = '".$at_msg."' ";
        sql_query($query, true);

        // 알림톡템플릿버튼
        if (count($buttons) && $buttons) {
            $z = 1;
            foreach ((array)$buttons as $key => $val) {

                if (!$val['type'])
                    continue;

                $atb_type = $ALIMTALK_BUTN_TYPE[$val['type']];

                $query = " insert into {$g5['wz_alimtalk_template_button_table']}
                     set at_tmplId = '".$at_tmplId."',
                         atb_type = '".$atb_type."',
                         atb_name = '".$val['name']."',
                         atb_url_mobile = '".$val['url_mobile']."',
                         atb_url_pc = '".$val['url_pc']."',
                         atb_scheme_android = '".$val['scheme_android']."',
                         atb_scheme_ios = '".$val['scheme_ios']."'
                         ";
                sql_query($query, true);
            }
        }

        $succ_count++;
    }
}

$g5['title'] = '템플릿 엑셀일괄등록 결과';
include_once(G5_PATH.'/head.sub.php');
?>

<style>
.local_desc01 {min-width:auto;margin:0 0 10px}
</style>

<div class="ifram-win">
    <h2><?php echo $g5['title']; ?></h2>

    <div class="local_desc01 local_desc">
        <p>템플릿등록을 완료했습니다.</p>
    </div>

    <dl id="excelfile_result">
        <dt>총템플릿수</dt>
        <dd><?php echo number_format($total_count); ?></dd>
        <dt>등록완료건수</dt>
        <dd><?php echo number_format($succ_count); ?></dd>
        <dt>등록실패건수</dt>
        <dd><?php echo number_format($fail_count); ?></dd>
    </dl>

    <div class="btn_win01 btn_win">
        <button type="button" onclick="parent.location.reload();">창닫기</button>
    </div>

</div>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>