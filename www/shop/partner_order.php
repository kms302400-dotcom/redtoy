<?php
include_once('./_common.php');

if (!$is_member)
    goto_url(G5_BBS_URL . "/login.php?url=" . urlencode(G5_SHOP_URL . '/partner_order.php'));

$g5['title'] = "파트너 정산 시스템";
include_once('./_head.php');

if($_GET['sdate']){
    $sdate = $_GET['sdate'];
}else{
    $sdate =  date('Y-m-d',strtotime($now."-1 months"));     // -1년

}

if($_GET['edate']){
    $edate = $_GET['edate'];

}else{
    $edate =  G5_TIME_YMD;
}

$p_status = $_GET['p_status'];
?>

<link rel="stylesheet" href="http://code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" type="text/css">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="http://code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<style>
    .tbl_head01 table {
        border-top: none;
        border-bottom: 1px solid #eee;
    }

    .tbl_head01 thead th {
        border-bottom: none;
        font-weight: 500;
        background: #eee;
    }

    .tbl_head01 tbody th {
        padding: 5px 0;
        background: #eee;
    }

    .tbl_head01 td {
        text-align: center;
    }

    .btn_submit {
        padding: 0 15px !important;
        height: 40px;
    }

    .sch_last {
        margin: 0 0 10px;
    }
</style>
<div style="clear: both;line-height: 30px;padding-top: 30px;"></div>
<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <div class="sch_last">
        <label for="sfl" class="sound_only">정산기간</label>
        <input type="text" name="sdate"  value="<?php echo $sdate ?>" id="sdate" class="required frm_input"> ~
        <input type="text" name="edate" value="<?php echo $edate ?>" id="edate" class="required frm_input">
        <select name="p_status" id="p_status" style="height: 40px; border: 1px solid #ddd; color: #5c5c5c;">
            <option value="" <?php if($p_status ==''){echo "selected";}?>>전체</option>
            <option value="1" <?php if($p_status =='1'){echo "selected";}?>>정산</option>
            <option value="0" <?php if($p_status =='0'){echo "selected";}?>>미정산</option>
        </select>
        <input type="submit" class="btn_submit" value="검색">    (정산비율 : <?php echo $member['mb_1']?> %      |    정산계좌 : <?php echo $member['mb_2']?> )
    </div>
</form>

<div class="partner_wrap tbl_head01">
    <table width="100%">

        <thead>
        <tr>
            <th scope="col">주문자</th>
            <th scope="col">결제수단</th>
            <th scope="col">처리상태</th>
            <th scope="col">상품명</th>
            <th scope="col">결제금액</th>
            <th scope="col">배송비</th>
            <th scope="col">정산전금액</th>
            <th scope="col">정산후금액</th>
            <th scope="col">정산상태</th>
            <th scope="col">주문시간</th>

        </tr>
        </thead>
        <?php
        $mb_id = $member['mb_id'];
        $mb_1 = $member['mb_1'] / 100;
        $d_edate =  date('Y-m-d',strtotime($edate."1 days"));     // -1년
        $sql = "select * from g5_shop_order where od_partner ='{$mb_id}' and od_time between '{$sdate}' and '{$d_edate}'";
        if(!empty($p_status)){
            $sql = $sql . " and od_partner_status = {$p_status}";
        }
        $result = sql_query($sql);

        for ($i = 0; $row = sql_fetch_array($result); $i++) {
            ?>


            <tbody>
            <tr>
                <td scope="col"><?php echo $row['od_name'] ?></td>
                <td scope="col"><?php echo $row['od_settle_case'] ?></td>
                <td scope="col"><?php echo $row['od_status'] ?></td>
                <td scope="col" style="text-align: center;">
                    <table width="100%">
                        <tr>
                            <th scope="col">제품명</th>
                            <th scope="col">제품금액</th>
                            <th scope="col">주문수량</th>
                        </tr>
                        <?php
                        $sql2 = "select it_name, ct_price, ct_qty from g5_shop_cart where od_id = {$row['od_id']}";
                        $result2 = sql_query($sql2);
                        for ($j = 0; $row2 = sql_fetch_array($result2); $j++) {
                            ?>
                            <tr>
                                <td><?php echo utf8_strcut($row2['it_name'],15) ?></td>
                                <td><?php echo number_format($row2['ct_price'], 0) ?></td>
                                <td><?php echo $row2['ct_qty'] ?></td>
                            </tr>

                            <?php

                        }
                        ?>
                    </table>
                </td>
                <td scope="col"><?php echo number_format($row['od_receipt_price'], 0) ?></td>
                <td scope="col"><?php echo $row['od_send_cost'] ?>/<?php echo $row['od_send_cost2'] ?></td>
                <td scope="col"><?php echo fn_partner_per($mb_id,$row['od_receipt_price'],1) ?></td>
                <td scope="col"><?php echo fn_partner_per($mb_id,$row['od_receipt_price'],$row['od_partner_status']) ?></td>
                <td scope="col"><?php if($row['od_partner_status']==0){echo "<span style='color:blue'>미정산</span>"; }else{echo "<span style='color:red'>정산완료</span>";} ?></td>
                <td scope="col"><?php echo $row['od_time'] ?></td>
            </tr>
            </tbody>

            <?php
            $sum += $row['od_receipt_price'] * $mb_1;
            $sum2 += fn_partner_amt($mb_id,$row['od_receipt_price'],$row['od_partner_status']);
        }
        ?>
        <tr>
            <td scope="col" colspan="6"></td>
            <td scope="col"><span style="color: blue">
            <?php echo number_format($sum, 0) ?></span></td>
            <td><span style="color: red"><?php echo number_format($sum2, 0) ?></span></td>
            <td scope="col" colspan="2"></td>
        </tr>
        <?
        if ($i == 0)
            echo '<div class="empty_table">정산내역이 존재하지 않습니다.</div>';
        ?>
    </table>

    <div style="margin: 30px 0 0;">
        <div id="container_title">정산내역</div>

        <table width="100%" style="margin: 30px 0 0;">
            <thead>
            <tr>
                <th scope="col" width="33%">정산 총금액</th>
                <th scope="col" width="33%">정산 완료 금액</th>
                <th scope="col" width="33%">미정산 금액</th>
            </tr>
            </thead>
            <tbody>
            <tr>

                <td scope="col"><span style="color: blue"><?php echo number_format($sum, 0) ?></span></td>
                <td><span style="color: red"><?php echo number_format($sum2, 0) ?></span></td>
                <td scope="col"><span style="color: black"><?php echo number_format($sum - $sum2, 0) ?></td>

            </tr>
            </tbody>
        </table>
    </div>
</div>


<?php
include_once('./_tail.php');
?>
<script>
    $(function () {
        $("#sdate").datepicker({
            dateFormat:'yy-mm-dd'
        });
        $("#edate").datepicker({
            dateFormat:'yy-mm-dd'
        });
    });
</script>



