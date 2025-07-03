<style type="text/css">
    .p-a {
        padding: 10px;
    }

    .bootstrap-tagsinput .badge {
        background-color: #009688;
        border: 1px solid #035d54;
    }

    button.close {
        padding: 0px;
    }

    .btn-block {
        width: 100%;
        display: block;
    }

    .panel-title {
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 1px;
    }

    #log_links {
        font-size: 14px;
        color: #333;
        background: #f8f8f8;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        min-height: 350px;
    }

    .crawl-btn {
        font-weight: bold;
        border-radius: 6px;
        margin-bottom: 14px;
        border: none;
        background: linear-gradient(90deg, #009688 0%, #26c6da 100%);
        color: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: background 0.3s;
    }

    .crawl-btn:hover, .crawl-btn:focus {
        background: linear-gradient(90deg, #26c6da 0%, #009688 100%);
        color: #fff;
    }

    @media (max-width: 991px) {
        .crawl-col, .log-col { width: 100%; max-width: 100%; }
    }

    #progress_wrap { margin: 10px 0; }
    #crawl_progress { width: 100%; height: 24px; background: #eee; border-radius: 6px; overflow: hidden; }
    #crawl_progress_bar { height: 100%; background: linear-gradient(90deg, #009688 0%, #26c6da 100%); width: 0%; color: #fff; text-align: center; line-height: 24px; font-weight: bold; transition: width 0.3s; }
</style>
<div class="row">
    <div class="col-md-5 crawl-col">
        <div class="panel panel-border panel-primary" style="margin-top: 20px;">
            <div class="panel-heading">
                <h3 class="panel-title">CRAWL THEO API CÓ SẴN</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div id="progress_wrap">
                        <div id="crawl_progress">
                            <div id="crawl_progress_bar">0%</div>
                        </div>
                    </div>
                    <label for="batch_size">Số phim mỗi lần crawl:</label>
                    <input id="batch_size" type="number" class="form-control" style="width:120px;display:inline-block;margin-bottom:10px;" min="10" max="1000" value="50" step="10" />
                    <button class="btn crawl-btn btn-block" id="crawl_all_auto" type="button">
                        ALL (AUTO)
                    </button>
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-6">
                            <input type="number" min="1" class="form-control" id="page_start" placeholder="Trang bắt đầu">
                        </div>
                        <div class="col-6">
                            <input type="number" min="1" class="form-control" id="page_end" placeholder="Trang kết thúc">
                        </div>
                    </div>
                    <button class="btn crawl-btn btn-block" id="crawl_page_range" type="button">
                        CRAWL PAGE
                    </button>
                    <div style="margin-bottom: 10px;">
                        <select class="form-control" id="crawl_category_select">
                            <option value="">-- Chọn chuyên mục --</option>
                            <option value="1">CENSORED</option>
                            <option value="2">UNCENSORED</option>
                            <option value="3">UNCENSORED LEAKED</option>
                            <option value="4">AMATEUR</option>
                            <option value="5">CHINESE AV</option>
                            <option value="6">WESTERN</option>
                        </select>
                        <button class="btn crawl-btn btn-block" id="crawl_category_btn" type="button" style="margin-top:5px;">CRAWL CATEGORY</button>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <div class="input-group">
                            <input type="text" class="form-control" id="crawl_search_input" placeholder="Nhập từ khóa hoặc ID">
                            <button class="btn crawl-btn btn-block" id="crawl_search_btn" type="button" style="margin-left:10px;">SEARCH</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-7 log-col">
        <div class="panel panel-border panel-primary" style="margin-top: 20px;">
            <div class="panel-heading">
                <h3 class="panel-title">Log crawl</h3>
            </div>
            <div class="panel-body">
                <textarea class="form-control" name="log_links" id="log_links" rows="18" readonly></textarea>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        function clearLog() {
            $("#log_links").val("");
        }
        function showLogResponse(response) {
            if (response && response.log) {
                if (Array.isArray(response.log)) {
                    $("#log_links").val(response.log.join("\n"));
                } else if (typeof response.log === 'string') {
                    $("#log_links").val(response.log);
                } else {
                    $("#log_links").val('Không có log trả về từ server.');
                }
            } else {
                $("#log_links").val('Không có log trả về từ server.');
            }
        }
        function updateProgress(done, total) {
            let percent = total > 0 ? Math.round(done * 100 / total) : 0;
            $("#crawl_progress_bar").css('width', percent + '%').text(percent + '%');
        }
        // Crawl theo batch cho tất cả các loại
        function crawl_batch(type, params, done, total, page, callback) {
            let batch_size = parseInt($('#batch_size').val());
            params.batch_size = batch_size;
            params.offset = done;
            if (page) params.page = page;
            $.ajax({
                type: "POST",
                url: params.url,
                data: params,
                dataType: "json",
                success: function (response) {
                    // Cập nhật log
                    let log = response.log || [];
                    if (!Array.isArray(log)) log = [log];
                    let newDone = response.done || (done + log.length);
                    let newTotal = response.total || total;
                    showLogResponse({log: log});
                    updateProgress(newDone, newTotal);
                    // Nếu còn phim thì gọi tiếp batch
                    if (response.has_more) {
                        setTimeout(function() {
                            crawl_batch(type, params, newDone, newTotal, response.page || page, callback);
                        }, 200);
                    } else {
                        if (typeof callback === 'function') callback();
                    }
                },
                error: function () {
                    $("#log_links").val("Lỗi kết nối server!");
                }
            });
        }
        // Crawl theo category
        $('#crawl_category_btn').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let cate = $('#crawl_category_select').val();
            let cateText = $('#crawl_category_select option:selected').text();
            if (!cate) {
                $('#log_links').val('Vui lòng chọn chuyên mục!');
                return;
            }
            $('#log_links').val('Đang crawl category: ' + cateText + ' ...');
            let params = {category_id: cate, url: "<?php echo base_url() . 'admin/crawl_by_category'; ?>"};
            crawl_batch('category', params, 0, 0, 1);
        });
        // Crawl all auto
        $('#crawl_all_auto').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            $('#log_links').val('Đang crawl ALL ...');
            let params = {url: "<?php echo base_url() . 'admin/crawl_avdb_auto_all'; ?>"};
            crawl_batch('all', params, 0, 0, 1);
        });
        // Crawl page range
        $('#crawl_page_range').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let start = $('#page_start').val();
            let end = $('#page_end').val();
            if (!start || !end || parseInt(end) < parseInt(start)) {
                $('#log_links').val('Vui lòng nhập số trang hợp lệ!');
                return;
            }
            $('#log_links').val('Đang crawl page range: ' + start + ' đến ' + end + ' ...');
            let params = {start: start, end: end, url: "<?php echo base_url() . 'admin/crawl_avdb_page_range'; ?>"};
            crawl_batch('range', params, 0, 0, parseInt(start));
        });
        // Crawl theo keyword hoặc ID
        $('#crawl_search_btn').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let value = $('#crawl_search_input').val().trim();
            if (!value) {
                $('#log_links').val('Vui lòng nhập từ khóa hoặc ID!');
                return;
            }
            let isId = /^\d+$/.test(value);
            if (isId) {
                $('#log_links').val('Đang crawl theo ID: ' + value + ' ...');
            } else {
                $('#log_links').val('Đang crawl theo keyword: ' + value + ' ...');
            }
            let params = {url: isId ? "<?php echo base_url() . 'admin/crawl_by_id'; ?>" : "<?php echo base_url() . 'admin/crawl_by_keyword'; ?>"};
            if (isId) params.id = value; else params.keyword = value;
            crawl_batch(isId ? 'id' : 'keyword', params, 0, 0, 1);
        });
    });
</script>