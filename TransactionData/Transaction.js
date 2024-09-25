var header_Transaction = function () {
    var menuName = "Transaction_", fd = "TransactionData/" + menuName + "data.php";

    function init() {
        loadData();
        reload_options_customer();
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
                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูล', callback: function () { } });
                }
                ele("dataT1").enable();
                //btn.enable();
            });
        }, 2000);
    };


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
                saveAs(e.data, 'tspkmr_transaction_route' + dayjs().format('YYYYMMDDTHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    function reload_options_customer() {
        var customerList = ele("Customer_Code").getPopup().getList();
        customerList.clearAll();
        customerList.load("common/customerMaster.php?type=2");

        $.post('common/customerMaster.php', { type: 5 })
            .done(function (data) {
                var json = JSON.parse(data);
                data = eval('(' + data + ')');
                if (json.ch == 1) {
                    var data1 = json.data;
                    if (data1.length <= 1) {
                        //console.log(data1);
                        ele('Customer_Code').setValue(data1[0].Customer_Code);
                    }
                }
            });
    };

    function getType() {
        if (!ele("status").getSelectedId()) {
            ele('tran_status').setValue('');
            return;
        }
        else {
            var select = ele("status").getSelectedId();
            var obj = "";
            obj += select + ",";
            var string = obj.substring(0, obj.length - 1);
            ele('tran_status').setValue(string);
        }

    }

    webix.ui({
        view: "popup",
        id: $n("my_pop"),
        width: 100,
        body: {
            view: "list",
            id: $n("status"),
            data: [
                { id: 'PLANNING', value: "PLANNING" },
                { id: 'IN-TRANSIT', value: "IN-TRANSIT" },
                { id: 'COMPLETE', value: "COMPLETE" },
                //{ id: 'CANCEL', value: "CANCEL" },
            ],
            template: "#value#",
            select: "multiselect",
            autoheight: true,
        }
    });

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_Transaction",
        body:
        {
            id: "Transaction_id",
            type: "space",
            rows:
                [
                    { view: "template", template: " TRNSACTION ROUTE", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements:
                            [
                                {
                                    cols:
                                        [
                                            vw1('combo', 'Customer_Code', 'Site', {
                                                required: false,
                                                suggest: "common/customerMaster.php?type=1",
                                                on: {
                                                    onBlur: function () {
                                                        this.getList().hide();
                                                    },
                                                    onItemClick: function () {
                                                        reload_options_customer();
                                                    },
                                                },
                                            }),
                                            vw2("datepicker", 'Start_Date2', 'Start_Date', "Start Date (วันเริ่ม)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat }),
                                            vw2("datepicker", 'Stop_Date2', 'Stop_Date', "Stop Date (วันหยุด)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat }),
                                            vw1("text", 'tran_status', "Transaction Type", {
                                                required: false,
                                                width: 400, popup: ele("my_pop"),
                                                hidden: 1,
                                                on: {
                                                    onItemClick: function () {
                                                        getType();
                                                    }
                                                }
                                            }),
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'find1', 'Find', {
                                                        width: 120, css: "webix_primary",
                                                        icon: "mdi mdi-magnify", type: "icon",
                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                        on:
                                                        {
                                                            onItemClick: function () {
                                                                loadData(this);
                                                                ele('status').unselectAll();
                                                            }
                                                        },
                                                        icon: "mdi mdi-magnify", type: "icon"
                                                    }),
                                                ]
                                            },
                                            {
                                                rows: [
                                                    {},
                                                    vw1("button", 'clear', "Clear", {
                                                        width: 120, css: "webix_secondary",
                                                        icon: "mdi mdi-backspace", type: "icon",
                                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                        on:
                                                        {
                                                            onItemClick: function () {
                                                                ele('status').unselectAll();
                                                                ele('tran_status').setValue('');
                                                                ele('Start_Date2').setValue(new Date());
                                                                ele('Stop_Date2').setValue(new Date());
                                                                ele('dataT1').clearAll();
                                                                //loadData(this);
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {
                                                rows: [
                                                    {},
                                                    vw1("button", 'btnExport', "Export", {
                                                        width: 120, css: "webix_orange",
                                                        icon: "mdi mdi-table-arrow-down", type: "icon",
                                                        tooltip: { template: "โหลดเป็นไฟล์ Excel", dx: 10, dy: 15 },
                                                        on:
                                                        {
                                                            onItemClick: function () {
                                                                var obj = ele('form1').getValues();
                                                                var dataT1 = ele("dataT1");
                                                                if (dataT1.count() != 0) {
                                                                    webix.extend($$("Transaction_id"), webix.ProgressBar);
                                                                    ele("btnExport").disable();
                                                                    $$("Transaction_id").showProgress({
                                                                        type: "top",
                                                                        delay: 5000,
                                                                        hide: true
                                                                    });

                                                                    setTimeout(function () {
                                                                        $.post(fd, { obj: obj, type: 2 })
                                                                            .done(function (data) {
                                                                                var json = JSON.parse(data);
                                                                                data = eval('(' + data + ')');
                                                                                if (json.ch == 1) {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: 'Export Complete', callback: function () {
                                                                                            ele("btnExport").enable();
                                                                                            $$("Transaction_id").hideProgress();
                                                                                            window.location.href = 'TransactionData/' + json.data;
                                                                                        }
                                                                                    });
                                                                                }
                                                                                else {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: 'ผิดพลาดโปรดลองอีกครั้ง', callback: function () {
                                                                                            ele("btnExport").enable();
                                                                                            $$("Transaction_id").hideProgress();
                                                                                            window.playsound(2);
                                                                                        }
                                                                                    });
                                                                                }
                                                                            })
                                                                    }, 0);

                                                                }
                                                                else {
                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", type: 'alert-error', ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                }
                                                            }
                                                        }
                                                    }),
                                                ]
                                            }
                                        ]
                                },
                                {
                                    view: "datatable", id: $n("dataT1"), navigation: true, select: "row",
                                    resizeColumn: true, autoheight: false, multiselect: true,
                                    hover: "myhover", threeState: true, rowLineHeight: 25, rowHeight: 25,
                                    datatype: "json", headerRowHeight: 25, leftSplit: 4, editable: true,
                                    datafetch: 50, // Number of rows to fetch at a time
                                    loadahead: 100, // Number of rows to prefetch
                                    scheme:
                                    {
                                        $change: function (item) {
                                            if (item.status == 'IN-TRANSIT') {
                                                item.$css = { "background": "#ffffb2", "font-weight": "bold" };
                                            }
                                            if (item.status == 'COMPLETE') {
                                                item.$css = { "background": "#afeac8", "font-weight": "bold" };
                                            }
                                        }
                                    },
                                    columns:
                                        [
                                            { id: "NO", header: "No", css: "rank", width: 50 },
                                            { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                            { id: "truck_Control_No_show", header: [{ text: "Truck Control No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                            { id: "truckNo_Date", header: [{ text: "Truck Control Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                            { id: "status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Route_Code", header: [{ text: "Route Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "line_CBM", header: [{ text: "CBM", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "pus_No_show", header: [{ text: "Pus No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name_Short", header: [{ text: "Supplier Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "sequence_Stop", header: [{ text: "Seq", css: { "text-align": "center" } }, { content: "textFilter" }], width: 60, css: { "text-align": "center" }, },
                                            { id: "Status_Pickup", header: [{ text: "Activity", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },
                                            { id: "planin_time", header: [{ text: "Plan In", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "planout_time", header: [{ text: "Plan Out", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "actual_in_time", header: [{ text: "actual In", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "actual_out_time", header: [{ text: "actual Out", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Truck_Number", header: [{ text: "Truck No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Truck_Type", header: [{ text: "Truck Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Remark", header: [{ text: "Remark", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Created_By_ID", header: [{ text: "Created By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Updated_By_ID", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "gps_Updated_By_ID", header: [{ text: "GPS Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "gps_datetime_connect", header: [{ text: "GPS Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "gps_connection", header: [{ text: "GPS Connection", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 0 },
                                        ],
                                    onClick:
                                    {
                                        "fa-pencil": function (e, t) {
                                            var row = this.getItem(t);
                                        },
                                    },
                                    on:
                                    {
                                        "onEditorChange": function (id, value) {

                                        }
                                    }
                                }
                            ]
                    }
                ], on:
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