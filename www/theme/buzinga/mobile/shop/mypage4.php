<?php
include_once('./_common.php');

$g5['title'] = '마이 페이지';

include_once(G5_THEME_MSHOP_PATH.'/shop.head.php');

///////////////////////////////////
//                               //
//                               //
//      리뷰관리 임시 페이지       //
//                               //
//                               //
///////////////////////////////////
?>
<link rel="stylesheet" href="/theme/buzinga/mobile/skin/member/basic/style.css">

<style>
    #container {max-width:none;padding:0;}
    #container_title {display:none}
</style>

<!-- 마이페이지 상단 공통 -->
<div id="mp_top">
    <h1>마이 페이지</h1>
    <ul class="mp_info">
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/couponzone.php">            
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_coupon_icon.png" alt="">
                <p>쿠폰 <span><?=number_format(get_coupon_count($member["mb_id"]))?></span> 장</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage3.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_point_icon.png" alt="">
                <p>포인트 <span><?=number_format(get_point_sum($member["mb_id"]))?></span> P</p>
            </a>
        </li>
        <li>
            <a href="<?php echo G5_SHOP_URL; ?>/wishlist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_wish_icon.png" alt="">
                <p>찜한 상품 <span><?=number_format(get_wishlist_datas_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
        <li>
            <a href="/bbs/qalist.php">
                <img src="<?php echo G5_THEME_IMG_URL; ?>/mp_qa_icon.png" alt="">
                <p>1:1 문의 <span><?=number_format(get_qna_count($member["mb_id"]))?></span> 개</p>
            </a>
        </li>
    </ul>
</div>

<div id="mypage_wrap">
    <div id="tab_menu">
        <ul>
            <li><a href="/shop/mypage.php">주문 배송 조회</a></li>
            <!-- 회원정보 수정 페이지 임시로 만들어놓음 -->
            <li><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage2.php">회원 정보 수정</a></li>
            <li class="active"><a href="<?php echo G5_THEME_MSHOP_URL; ?>/mypage4.php">리뷰 관리</a></li>
            <li><a href="/bbs/qalist.php">1:1 문의</a></li>
        </ul>
    </div>
<?
    $page = isset($_REQUEST["page"]) ? safe_replace_regex($_REQUEST["page"], "number") : 1;
    $page_size = 20;
    
    $sql = "select
            	count(*) as cnt
            from
            	(
            	SELECT
            		it.*,
            		'' as ct_id,
            		'' as io_id,
            		iut.is_id,
            		iut.is_ip,
            		iut.is_name,
            		iut.is_score,
            		iut.is_content,
            		iut.is_time
            	FROM
            		g5_shop_item_use as iut
            	inner join g5_shop_item as it on
            		iut.it_id = it.it_id
            	WHERE
            		iut.mb_id = '" . $member["mb_id"] . "'
            		and ct_id = ''
            union all
            	SELECT
            		it.*,
            		ct.ct_id,
            		ct.io_id,
            		iut.is_id,
            		iut.is_ip,
            		iut.is_name,
            		iut.is_score,
            		iut.is_content,
            		ifnull(ct.ct_time, iut.is_time) as is_time
            	FROM
            		g5_shop_cart as ct
            	left join g5_shop_item_use as iut on
            		iut.ct_id = ct.ct_id
            	inner join g5_shop_item as it on
            		ct.it_id = it.it_id
            	WHERE
            		ct.mb_id = '" . $member["mb_id"] . "' ) as list ";
    $total_row = sql_fetch($sql);
    $total_count = $total_row["cnt"];
    
    $sql = " select * from
            	(
            	SELECT
            		it.*,
            		'' as ct_id,
            		'' as io_id,
            		iut.is_id,
            		iut.is_ip,
            		iut.is_name,
            		iut.is_score,
            		iut.is_content,
            		iut.is_time
            	FROM
            		g5_shop_item_use as iut
            	inner join g5_shop_item as it on
            		iut.it_id = it.it_id
            	WHERE
            		iut.mb_id = '" . $member["mb_id"] . "'
            		and ct_id = ''
            union all
            	SELECT
            		it.*,
            		ct.ct_id,
            		ct.io_id,
            		iut.is_id,
            		iut.is_ip,
            		iut.is_name,
            		iut.is_score,
            		iut.is_content,
            		ifnull(ct.ct_time, iut.is_time) as is_time
            	FROM
            		g5_shop_cart as ct
            	left join g5_shop_item_use as iut on
            		iut.ct_id = ct.ct_id
            	inner join g5_shop_item as it on
            		ct.it_id = it.it_id
            	WHERE
            		ct.mb_id = '" . $member["mb_id"] . "' ) as list ";
    $sql .= " ORDER BY list.is_time DESC LIMIT " . (($page - 1) * $page_size) . "," . ($page * $page_size);
    $result = sql_query($sql);
?>
    <div id="smb_my" class="mp_review">
        <p class="r_txt">리뷰 작성 시 포토 500P / 일반 100P 지급!</p>  
        <ul class="mp_order">
            <?
            for($i=0; $row=sql_fetch_array($result); $i++) {
                $itemuse_form = G5_SHOP_URL."/itemuseform.php?it_id=".$row['it_id'];
                $itemuse_formupdate = G5_SHOP_URL."/itemuseformupdate.php?it_id=".$row['it_id'];
                
                if ($row["is_id"]) {
                    $hash = md5($row['is_id'].$row['is_time'].$row['is_ip']);
            ?>
            <li>
                <div class="order_info">
                    <div class="product_info">
                        <img class="it_img" data-itid="<?=$row["it_id"]?>" src="/product_image.php?it_id=<?=$row["it_id"]?>" width="70" height="70" alt="test">
                        <div class="product_details">
                            <p class="p_date"><?=$row["is_date"]?></p>
                            <p class="it_brand"><?=$row["it_brand"]?></p>
                            <p class="it_name"><?=$row["it_name"]?></p>
                            <? if ($row["io_id"]) { ?>
                            <div class="product_option">
                                <p><?=$row["io_id"]?></p>
                            </div>
                            <? } ?>
                        </div>
                        <div class="order_price">
                            <a href="<?php echo $itemuse_form."&amp;is_id={$row['is_id']}&amp;w=u"; ?>" class="itemuse_form btn01 review_edit" onclick="return false;">수정</a>
                            <a href="<?php echo $itemuse_formupdate."&amp;is_id={$row['is_id']}&amp;w=d&amp;hash={$hash}&amp;page={$page}&amp;returnuri=/theme/buzinga/mobile/shop/mypage4.php"; ?>" class="itemuse_delete btn01">삭제</a>
                        </div>   
                    </div>
                    <div class="product_review">
                        <div class="review_score">
                            <img src="/shop/img/s_star<?=$row["is_score"]?>.png">
                        </div>
                        <div class="review_txt"><?=$row["is_content"]?></div>
                        <div class="review_photo">
                            <?
                                $sql = " SELECT * FROM g5_shop_item_use_image WHERE is_id = '" . $row["is_id"] . "' ";
                                $res2 = sql_query($sql);
                                $rows2 = sql_num_rows($res2);
                                
                                for($j=0;$j<$rows2;$j++) {
                                    $row2 = sql_fetch_array($res2);
                            ?>
                            <a onclick="reviewDetailOpen(<?=$row2['is_id']?>)">
                                <img src="/review_image.php?bf_file=<?=$row2["bf_file"];?>" width="70">
                            </a>
                            <?
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </li>
            <? 
                } else {
            ?>
            <li>
                <div class="order_info">
                    <div class="product_info">
                        <img class="it_img" data-itid="<?=$row["it_id"]?>" data-ctid="<?=$row["ct_id"]?>" src="/product_image.php?it_id=<?=$row["it_id"]?>" width="70" height="70" alt="test">
                        <div class="product_details">
                            <p class="p_date"><?=$row["is_date"]?></p>
                            <p class="it_brand"><?=$row["it_brand"]?></p>
                            <p class="it_name"><?=$row["it_name"]?></p>
                            <? if ($row["io_id"]) { ?>
                            <div class="product_option">
                                <p><?=$row["io_id"]?></p>
                            </div>
                            <? } ?>
                        </div>
                        <div class="order_price">
                            <a href="<?php echo $itemuse_form."&amp;ct_id=".$row["ct_id"]."&amp;is_id=&amp;w="; ?>" class="itemuse_form blk_btn review_write" onclick="return false;">리뷰 작성</a>
                        </div>   
                    </div>
                    
                </div>
            </li>
            <?
                }
            }
            ?>
        </ul>
        
        <?
        echo PageNumber($page, $page_size, $total_count, "");
        ?>
    </div>
</div>
<div id="popupContainer2" class="popup-container review_write_popup" >
    <div class="popup">
        <span id="closePopup2" class="close-btn">×</span>
        <div>
            <?php
            include_once(G5_MSHOP_SKIN_PATH.'/itemuseform.skin.php');
            ?>
        </div>
    </div>
</div>
<div id="popupContainer4" class="popup-container review_detail" >
    <div class="popup">
        <span id="closePopup4" class="close-btn">×</span>
        <div>
            <?php
            include_once(G5_MSHOP_SKIN_PATH.'/itemusedetail.skin.php');
            ?>
        </div>
    </div>
</div>
<div id="popupContainer5" class="popup-container review_edit_popup" >
    <div class="popup">
        <span id="closePopup5" class="close-btn">×</span>
        <div id="sit_use_modify"></div>
    </div>  
</div>
<style>
    .review_txt img { width: 70px }
</style>
<script>
    $(document).ready(() => {
        $(".review_write").click(function(){
            var img = $(this).parent().parent().find("img")
            var it_id = $(img).data("itid");
            var ct_id = $(img).data("ctid");
            var it_brand = $(this).parent().parent().find(".it_brand").html();
            var it_name = $(this).parent().parent().find(".it_name").html();
            
            $("#product_image").attr("src", "/product_image.php?it_id=" + it_id);
            $("#use_it_id").val(it_id);
            $("#use_ct_id").val(ct_id);
            $("#is_subject").val(it_name);
            $("#it_brand").html(it_brand);
            $("#it_name").html(it_name);
            fileIndex = 0;
            dataTransfer = new DataTransfer();
            $(".review_write_popup").show();
        });
        
        $(".review_edit").click(function(){
            $.ajax({
                url : this.href.replace("itemuseform", "itemedit"), 
                type : "POST"
            }).done((res) => {
                $("#sit_use_modify").html(res)
                dataTransfer = new DataTransfer();
                $(".review_edit_popup").show();
            })
            // window.open(this.href, "itemuse_form", "width=810,height=680,scrollbars=1");
            // return false;
        });
        

        $(".itemuse_delete").click(function(){
            if (confirm("정말 삭제 하시겠습니까?\n\n삭제후에는 되돌릴수 없습니다.")) {
                return true;
            } else {
                return false;
            }
        });
    })

    document.getElementById('closePopup2').addEventListener('click', function() {
        document.getElementById('popupContainer2').style.display = 'none';
    });
    document.getElementById('closePopup4').addEventListener('click', function() {
        document.getElementById('popupContainer4').style.display = 'none';
    });
    document.getElementById('closePopup5').addEventListener('click', function() {
        document.getElementById('popupContainer5').style.display = 'none';
    });
    
    function reviewDetailOpen(is_id){
        var photo_list = $("#sit_use_photo_all").find("ul").html()
        $("#detail_photo_list_all").html(photo_list);
        
        $.ajax({
            url : "/shop/ajax.review.php"
            , type : "POST"
            , data : { "is_id" : is_id }
        }).done((res) => {
            try {
                var json = typeof res === "string" ? JSON.parse(res) : res
                
                $("#detail_score_image").attr("src", "/shop/img/s_star"+json.is_score+".png");
                $("#detail_score").html(json.is_score);
                $("#detail_username").text(json.is_name);
                $("#detail_review_notice").text(json.review_notice || "");
                $("#detail_date").text(json.is_time.split(" ")[0]);
                $("#detail_content").html(json.is_content);
                $("#detail_image_list").html("");
                
                var position = 0;
                json.image_list.forEach((v) => {
                    if (position == 0) {
                        $("#detail_image").attr("src", "/review_image.php?bf_file=" + v.bf_file);
                    }
                    $("#detail_image_list").append("<img src='/review_image.php?bf_file=" + v.bf_file + "' onclick='viewDetailImage(this)' width='70' height='70' />");
                    position++;
                })
                
                $('#popupContainer4').show();
            } catch {
                //
            }
        })
    }
    function viewDetailImage(obj) {
        $("#detail_image").attr("src", $(obj).attr("src"));
    }
    
    $(document).ready(() => {
        $(document).on("keydown", (e) => {
            if (event.keyCode == 27) {
                $("#popupContainer1").hide();
                $("#popupContainer2").hide();
                $("#popupContainer3").hide();
                $("#popupContainer4").hide();
                $("#popupContainer5").hide();
            }
        })
    })
    
    var fileIndex = 0
    var fileArray = new Array();
    var fileList = null;
    var dataTransfer = new DataTransfer();
    $(document).ready(() => {
        $("#review_file_load").on("change", fileChangeUpload);
    })

    function fileChangeUpload() {
        <? if (is_mobile()) { ?>
        alert("포토후기는 PC에서만 등록 가능합니다")
        <? } else { ?>
    	readURL(this);
    	
    	let fileArr = document.getElementById("review_file_load").files

    	if(fileArr != null && fileArr.length > 0){
    		for(var i=0; i<fileArr.length; i++){
    			dataTransfer.items.add(fileArr[i])
    		}
    		document.getElementById("review_file_load").files = dataTransfer.files;
    	}
        <? } ?>
    }

    function fileChangeUpload2() {
    	readURL2(this);
    	
    	let fileArr = document.getElementById("edit_review_file_load").files

    	if(fileArr != null && fileArr.length > 0){
    		for(var i=0; i<fileArr.length; i++){
    			dataTransfer.items.add(fileArr[i])
    		}
    		document.getElementById("edit_review_file_load").files = dataTransfer.files;
    	}
    }

    function reviewUpload() {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $("#review_file_load").click();
    }

    function reviewUpload2() {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $("#edit_review_file_load").click();
    }

    function readURL(input) {
        if (fileIndex >= 5) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $(input.files).each((i, e, v) => {
            $(".review_img").show()
            if (input.files[i]) {
                if (fileIndex > 4) {
                    alert("최대 5개까지 업로드 가능합니다")
                    return
                } else {
                    let uuid = uuidv4();
                    $(".review_img").append('<span id="previewImageIndent' + uuid + '"><span class="close" onclick="deleteImage(\'' + uuid + '\', \'\')">&#10005;</span><img id="previewImage' + uuid + '" style="width:70px;height:70px" /></span>')
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage' + uuid).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[i]);
                    fileArray.push({ "uuid" : uuid, "idx" : fileIndex })
                    
                    fileIndex++;
                    $("#file_count").html(fileIndex);
                }
            }
        })
    }

    function readURL2(input) {
        if (fileIndex > 4) {
            alert("최대 5개까지 업로드 가능합니다")
            return false
        }
        
        $(input.files).each((i, e, v) => {
            $(".edit_review_img").show()
            console.log(input.files[i])
            if (input.files[i]) {
                if (fileIndex > 4) {
                    alert("최대 5개까지 업로드 가능합니다")
                    return
                } else {
                    let uuid = uuidv4();
                    $(".edit_review_img").append('<span id="previewImageIndent' + uuid + '"><span class="close" onclick="deleteImage2(\'' + uuid + '\', \'\')">&#10005;</span><img id="previewImage' + uuid + '" style="width:70px;height:70px" /></span>')
                    
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#previewImage' + uuid).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[i]);
                    fileArray.push({ "uuid" : uuid, "idx" : fileIndex })
                    
                    fileIndex++;
                    $(".edit_file_count").html(fileIndex);
                }
            }
        })
    }

    function deleteImage(uuid, bf_file) {
        $("#previewImageIndent" + uuid).remove();
        var fileObject = fileArray.find(x => x.uuid == uuid)
        if (fileObject) {
            var fileIdxs = fileObject.idx
            
        	dataTransfer.items.remove(fileIdxs)
            document.getElementById("review_file_load").files = dataTransfer.files;
        }
        fileIndex--;
        $("#file_count").html(fileIndex);
        if (fileIndex == 0) $(".review_img").hide()
    }

    function deleteImage2(uuid, bf_file) {
        $("#previewImageIndent" + uuid).remove();
        var fileObject = fileArray.find(x => x.uuid == uuid)
        if (fileObject) {
            var fileIdxs = fileObject.idx
            
        	dataTransfer.items.remove(fileIdxs)
            $(".edit_review_file_load").files = dataTransfer.files;
        }
        fileIndex--;
        $(".edit_file_count").html(fileIndex);
        if (fileIndex == 0) $(".review_img").hide()
        if (bf_file) {
            $input = $("<input type='hidden' name='delfile[]' value='" + bf_file + "' />");
            $(document.fitemuseedit).append($input)
        }
    }
</script>
<?php
include_once(G5_THEME_MSHOP_PATH.'/shop.tail.php');
?>