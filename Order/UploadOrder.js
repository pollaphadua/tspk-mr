var header_UploadOrder = function () {
    var menuName = "UploadOrder_", fd = "Order/" + menuName + "data.php";

    function init() {
        refreshAt(0, 0, 0); //Will refresh the page at 00:00
        loadData();
    };

    function ele(name) {
        return $$($n(name));
    };

    function $n(name) {
        return menuName + name;
    };

    function focus(name) {
        setTimeout(function () { ele(name).focus(); }, 100);
    };

    function setView(target, obj) {
        var key = Object.keys(obj);
        for (var i = 0, len = key.length; i < len; i++) {
            target[key[i]] = obj[key[i]];
        }
        return target;
    };

    function vw1(view, name, label, obj) {
        var v = { view: view, required: true, label: label, id: $n(name), name: name, labelPosition: "top" };
        return setView(v, obj);
    };

    function vw2(view, id, name, label, obj) {
        var v = { view: view, required: true, label: label, id: $n(id), name: name, labelPosition: "top" };
        return setView(v, obj);
    };

    function vw3(view, id, name, label, obj) {
        var v = { view: view, required: true, label: label.replace(/_/g, " "), id: $n(id + name), name: name, labelPosition: "top" };
        var view = setView(v, obj);

        if (view.required == true) {
            view.label = "<font color='red'>" + view.label + "</font>";
        }
        else {
            view.label = "<font color='green'>" + view.label + "</font>";
        }
        return view;
    };

    function refreshAt(hours, minutes, seconds) {
        var now = new Date();
        var then = new Date();

        if (now.getHours() > hours ||
            (now.getHours() == hours && now.getMinutes() > minutes) ||
            now.getHours() == hours && now.getMinutes() == minutes && now.getSeconds() >= seconds) {
            then.setDate(now.getDate() + 1);
        }
        then.setHours(hours);
        then.setMinutes(minutes);
        then.setSeconds(seconds);

        var timeout = (then.getTime() - now.getTime());
        setTimeout(function () { window.location.reload(true); }, timeout);
    }

    function exportExcel(btn) {
        var dataT1 = ele("dataT1"), obj = {}, data = [];
        if (dataT1.count() == 0) {
            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
        }

        for (var i = -1, len = dataT1.config.columns.length; ++i < len;) {
            obj[dataT1.config.columns[i].id] = dataT1.config.columns[i].header[0].text;
        }
        delete obj.icon_edit;
        var objKey = Object.keys(obj);
        var f = [];
        for (var i = -1, len = objKey.length; ++i < len;) {
            f.push(objKey[i]);
        }

        var col = [];
        for (var i = -1, len = f.length; ++i < len;) {
            col[col.length] = obj[f[i]];
        }
        data[data.length] = col;
        if (dataT1.count() > 0) {
            btn.disable();
            dataT1.eachRow(function (row) {
                var r = dataT1.getItem(row), rr = [];
                for (var i = -1, len = f.length; ++i < len;) {
                    rr[rr.length] = r[f[i]];
                }
                data[data.length] = rr;
            });

            var worker = new Worker('js/workerToExcel.js?v=1');
            worker.addEventListener('message', function (e) {
                saveAs(e.data, 'tspkmr_order_upload' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function setTable(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
        ele(tableName).filterByAll();
    };

    function loadData(btn) {
        webix.extend(ele("dataT1"), webix.ProgressBar);
        ele("dataT1").disable();
        //btn.disable();
        ele("dataT1").showProgress({
            type: "bottom",
            delay: 2000,
            hide: true
        });
        setTimeout(function () {
            var obj = ele('form1').getValues();
            ajax(fd, obj, 1, function (json) {
                //console.log(json.data);
                setTable('dataT1', json.data);
                var dtable = ele("dataT1"), obj = {}, data = [];
                if (dtable.count() == 0) {
                    //webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูล', callback: function () { } });
                }
                ele("dataT1").enable();
                //btn.enable();
            });
        }, 2000);
    };

    // function loadData(btn) {
    //     var obj = ele('form1').getValues();
    //     ajax(fd, obj, 1, function (json) {
    //         setTable('dataT1', json.data);
    //     }, null,
    //         function (json) {
    //             //ele('find').callEvent("onItemClick", []);
    //         }, btn);
    // };

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_UploadOrder",
        body:
        {
            id: "UploadOrder_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "UPLOAD ORDER", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'export1', 'Export Data', {
                                                    width: 120, css: "webix_orange",
                                                    icon: "mdi mdi-table-arrow-down", type: "icon",
                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                    on:
                                                    {
                                                        onItemClick: function () {
                                                            exportExcel(this);
                                                            // exportExcelHistory(this);
                                                        }
                                                    },
                                                }),
                                            ]
                                        },
                                        {
                                            rows: [
                                                {},
                                                vw1("uploader", 'btn_upload_file', "Upload Data", {
                                                    width: 120, css: "webix_blue",
                                                    icon: "mdi mdi-upload", type: "icon",
                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                    on:
                                                    {
                                                        onBeforeFileAdd: function (file) {
                                                            var type = file.type.toLowerCase();
                                                            if (type == "txt") {

                                                            }
                                                            else {
                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", text: "รองรับ CSV ,XLS ,XLSX เท่านั้น", type: 'alert-error' });
                                                                return false;
                                                            }
                                                            ele("btn_upload_file").disable();
                                                        },
                                                        onAfterFileAdd: function (item) {
                                                            var formData = new FormData();
                                                            this.files.data.each(function (obj, i) {
                                                                formData.append("upload", obj.file);
                                                            });
                                                            $.ajax({
                                                                type: 'POST',
                                                                cache: false,
                                                                contentType: false,
                                                                processData: false,
                                                                url: fd + '?type=54',
                                                                data: formData,
                                                                success: function (data) {
                                                                    ele("btn_upload_file").enable();
                                                                    loadData();
                                                                    var json = JSON.parse(data);
                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                                }
                                                            });
                                                        },
                                                    },
                                                }),
                                            ]
                                        },
                                        {},
                                        vw1("datepicker", 'Start_Date', "Start Date (วันเริ่ม)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 150, required: false }),
                                        vw1("datepicker", 'Stop_Date', "Stop Date (วันหยุด)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 150, required: false }),
                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'find', 'Find', {
                                                    width: 120, css: "webix_primary",
                                                    icon: "mdi mdi-magnify", type: "icon",
                                                    tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                    on: {
                                                        onItemClick: function (id, e) {
                                                            loadData();
                                                        }
                                                    }
                                                }),
                                            ]
                                        },
                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'btn_clear', 'Clear', {
                                                    width: 120, css: "webix_secondary",
                                                    icon: "mdi mdi-backspace", type: "icon",
                                                    tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                    on: {
                                                        onItemClick: function () {
                                                            ele('Start_Date').setValue(new Date());
                                                            ele('Stop_Date').setValue(new Date());
                                                            loadData();
                                                            ele('dataT1').eachColumn(function (id, col) {
                                                                var filter = this.getFilter(id);
                                                                if (filter) {
                                                                    if (filter.setValue) filter.setValue("")
                                                                    else filter.value = "";
                                                                }
                                                            });

                                                        }
                                                    },
                                                }),
                                            ]
                                        },
                                    ]
                            },
                            {
                                cols: [
                                    {
                                        view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                        resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                        datatype: "json", headerRowHeight: 25, leftSplit: 5, editable: true,
                                        pager: $n("Master_pagerA"),
                                        datafetch: 50, // Number of rows to fetch at a time
                                        loadahead: 100, // Number of rows to prefetch
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                // var css = {};
                                                // obj.$cellCss = css;
                                                if (obj.Process_Status != 'Process_Complete') {
                                                    obj.$css = { "background": "#ff8f8f", "font-weight": "bold" };
                                                }
                                                if (obj.Process_Status == 'Not_Found' && obj.Command == 'UPDATE') {
                                                    obj.$css = { "background": "#ffc08f", "font-weight": "bold" };
                                                }
                                            } 
                                        },
                                        columns: [
                                            // {
                                            //     id: "icon_edit", header: "&nbsp;", width: 50, template: function (row) {
                                            //         return "<button class='mdi mdi-pencil webix_button' style='width:30px; height:20px; color:#ffffff; background-color: #68A4C4;'></button>";
                                            //     }
                                            // },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "Order_ID", header: ["Order_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                            { id: "Process_Status", header: [{ text: "Process Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                            { id: "Pickup_Date", header: [{ text: "Pickup Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                            { id: "Project", header: [{ text: "Project", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Product_Code", header: [{ text: "Product Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Part_No", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Part_Name", header: [{ text: "Part Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "Supplier_Code", header: [{ text: "Supplier Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "Qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 70, css: { "text-align": "center" }, },
                                            { id: "UM", header: [{ text: "UM", css: { "text-align": "center" } }, { content: "textFilter" }], width: 70, css: { "text-align": "center" }, },
                                            //{ id: "Actual_Qty", header: [{ text: "Actual Qty", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "PO_No", header: [{ text: "PO No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "PO_Line", header: [{ text: "PO Line", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "PO_Release", header: [{ text: "PO Relase", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Command", header: [{ text: "Command", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Refer_ID", header: [{ text: "Refer ID.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        ],
                                        onClick:
                                        {
                                            "mdi-pencil": function (e, t) {
                                                ele('win_edit').show();
                                                var row = this.getItem(t);
                                                ele('win_edit_form').setValues(row);
                                            },
                                        },
                                        on: {
                                            // "onEditorChange": function (id, value) {
                                            // }
                                            "onItemClick": function (id) {
                                                this.editRow(id);
                                            }
                                        }
                                    },
                                ],
                            },
                            {
                                cols: [
                                    {},
                                    {
                                        view: "pager", id: $n("Master_pagerA"),
                                        template: function (data, common) {
                                            var start = data.page * data.size
                                                , end = start + data.size;
                                            if (data.count == 0) start = 0;
                                            else start += 1;
                                            if (end >= data.count) end = data.count;
                                            var html = "<b>showing " + (start) + " - " + end + " total " + data.count + " </b>";
                                            return common.first() + common.prev() + " " + html + " " + common.next() + common.last();
                                        },
                                        size: 5000,
                                        group: 5
                                    },
                                    {}
                                ]
                            }
                        ]
                    },
                ]

            , on:
            {
                onHide: function () {

                },
                onShow: function () {

                },
                onAddView: function () {
                    init();
                }
            }
        }
    };
};