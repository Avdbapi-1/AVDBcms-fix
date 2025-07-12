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
                <h3 class="panel-title">CRAWL FROM API</h3>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div id="progress_wrap">
                        <div id="crawl_progress">
                            <div id="crawl_progress_bar">0%</div>
                        </div>
                        <div id="page_status" style="margin-top: 5px; font-weight: bold; text-align: center;"></div>
                    </div>
                    <label for="batch_size">Movies per batch:</label>
                    <input id="batch_size" type="number" class="form-control" style="width:120px;display:inline-block;margin-bottom:10px;" min="10" max="1000" value="50" step="10" />
                    <button class="btn crawl-btn btn-block" id="crawl_all_auto" type="button">
                        ALL (AUTO)
                    </button>
                    <div class="row" style="margin-bottom:10px;">
                        <div class="col-6">
                            <input type="number" min="1" class="form-control" id="page_start" placeholder="Start page">
                        </div>
                        <div class="col-6">
                            <input type="number" min="1" class="form-control" id="page_end" placeholder="End page">
                        </div>
                    </div>
                    <button class="btn crawl-btn btn-block" id="crawl_page_range" type="button">
                        CRAWL PAGE
                    </button>
                    <div style="margin-bottom: 10px;">
                        <select class="form-control" id="crawl_category_select">
                            <option value="">-- Select category --</option>
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
                            <input type="text" class="form-control" id="crawl_search_input" placeholder="Enter keyword or ID">
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
                <h3 class="panel-title">Crawl Log</h3>
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
                    $("#log_links").val('No log returned from server.');
                }
            } else {
                $("#log_links").val('No log returned from server.');
            }
        }
        function updateProgress(done, total) {
            let percent = total > 0 ? Math.round(done * 100 / total) : 0;
            $("#crawl_progress_bar").css('width', percent + '%').text(percent + '%');
        }
        function crawl_page_by_page(type, params, page, allLog, total_pages) {
            if (!allLog) allLog = [];
            
            if (page === params.start_page) {
                $('#log_links').val(params.initial_message + '\n--------------------');
            }
            
            $('#page_status').text('Status: Processing page ' + page + (total_pages > 0 ? ' / ' + total_pages : '...'));
            if(total_pages > 0) updateProgress(page - 1, total_pages);

            params.page = page;

            $.ajax({
                type: "POST",
                url: params.url,
                data: params,
                dataType: "json",
                success: function (response) {
                    if (response.status === 'fail' || !response.log || response.log.length === 0) {
                         $('#page_status').text('Status: Completed or error on page ' + page);
                         allLog.push('--------------------\nCOMPLETED!');
                         showLogResponse({ log: allLog });
                         if(total_pages > 0) updateProgress(total_pages, total_pages);
                         return;
                    }

                    let new_total_pages = response.pagecount || total_pages || 0;
                    
                    allLog.push('--- Results for page ' + page + ' / ' + new_total_pages + ' ---');
                    allLog = allLog.concat(response.log);
                    showLogResponse({ log: allLog });
                    updateProgress(page, new_total_pages);

                    if (response.has_more) {
                        setTimeout(function () {
                            crawl_page_by_page(type, params, page + 1, allLog, new_total_pages);
                        }, 500);
                    } else {
                        $('#page_status').text('Status: Completed ' + new_total_pages + ' pages.');
                        allLog.push('--------------------\nCOMPLETED ALL!');
                        showLogResponse({ log: allLog });
                        updateProgress(new_total_pages, new_total_pages);
                    }
                },
                error: function () {
                    $('#page_status').text('Status: Server connection error!');
                    $("#log_links").val(allLog.join('\n') + '\nServer connection error!');
                }
            });
        }
        $('#crawl_category_btn').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let cate = $('#crawl_category_select').val();
            let cateText = $('#crawl_category_select option:selected').text();
            if (!cate) {
                $('#log_links').val('Please select a category!');
                return;
            }
            let params = {category_id: cate, url: "<?php echo base_url() . 'admin/crawl_by_category'; ?>"};
            params.initial_message = 'Crawling category: ' + cateText + ' ...';
            params.start_page = 1;
            crawl_page_by_page('category', params, 1);
        });
        $('#crawl_all_auto').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let params = {url: "<?php echo base_url() . 'admin/crawl_avdb_auto_all'; ?>"};
            params.initial_message = 'Crawling ALL ...';
            params.start_page = 1;
            crawl_page_by_page('all', params, 1);
        });
        $('#crawl_page_range').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let start = $('#page_start').val();
            let end = $('#page_end').val();
            if (!start || !end || parseInt(end) < parseInt(start)) {
                $('#log_links').val('Please enter a valid page range!');
                return;
            }
            let params = {start: start, end: end, url: "<?php echo base_url() . 'admin/crawl_avdb_page_range'; ?>"};
            params.initial_message = 'Crawling page range: ' + start + ' to ' + end + ' ...';
            params.start_page = parseInt(start);
            crawl_page_by_page('range', params, parseInt(start));
        });
        $('#crawl_search_btn').on('click', function() {
            clearLog();
            updateProgress(0, 1);
            let value = $('#crawl_search_input').val().trim();
            if (!value) {
                $('#log_links').val('Please enter a keyword or ID!');
                return;
            }
            let isId = /^\d+$/.test(value);
            let params = {url: isId ? "<?php echo base_url() . 'admin/crawl_by_id'; ?>" : "<?php echo base_url() . 'admin/crawl_by_keyword'; ?>"};
            if (isId) params.id = value; else params.keyword = value;
            params.initial_message = 'Crawling ' + (isId ? 'by ID: ' : 'by keyword: ') + value + ' ...';
            params.start_page = 1;
            crawl_page_by_page(isId ? 'id' : 'keyword', params, 1);
        });
    });
</script>