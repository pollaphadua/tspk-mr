var header_ViewOrder = function () {
    var menuName = "ViewOrder_", fd = "Order/" + menuName + "data.php";

    function init() {
        refreshAt(0, 0, 0); //Will refresh the page at 00:00
        //setStartDate();
        setLastDate();
        // loadData();
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

    function getFirstDayOfMonth(year, month) {
        return new Date(year, month, 1);
    }

    function setStartDate() {
        const date = new Date();
        const firstDay = getFirstDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );
        ele('Start_Date').setValue(firstDay);
    }

    function getLastDayOfMonth(year, month) {
        return new Date(year, month + 1, 0);
    };

    function setLastDate() {
        const date = new Date();
        const LastDay = getLastDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );
        ele('Stop_Date').setValue(LastDay);
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


    webix.editors.editdate = webix.extend({
        render: function () {
            var icon = "<span class='webix_icon wxi-calendar' style='position:absolute; cursor:pointer; top:3px; right:5px;'></span>";
            var node = webix.html.create("div", {
                "class": "webix_dt_editor"
            }, "<input type='text'>" + icon);

            node.childNodes[1].onclick = function () {
                var master = webix.UIManager.getFocus();
                var editor = master.getEditor();

                master.editStop(false);
                var config = master.getColumnConfig(editor.column);
                config.editor = "date";
                master.editCell(editor.row, editor.column);
                config.editor = "editdate";
            }
            return node;
        }
    }, webix.editors.text);

    // function loadData(btn) {
    //     var obj = ele('form1').getValues();
    //     //console.log(obj);
    //     ajax(fd, obj, 1, function (json) {
    //         setTable('dataT1', json.data);
    //         if (json.data.length == 0) {
    //             setTable('dataT1', json.data);
    //         }
    //     }, btn,
    //         function (json) {
    //             /* ele('find').callEvent("onItemClick", []); */
    //         });
    // }

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
                saveAs(e.data, 'tspkmr_order' + dayjs().format('YYYYMMDDTHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_ViewOrder",
        body:
        {
            id: "ViewOrder_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "VIEW ORDER", type: "header" },
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
                                        datatype: "json", headerRowHeight: 25, leftSplit: 5,
                                        editable: true, editaction: "dblclick",
                                        pager: $n("Master_pagerA"),
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                if (obj.Actual_Qty > obj.Qty) {
                                                    obj.$css = { "background": "#ffc08f", "font-weight": "bold" };
                                                }
                                            }
                                        },
                                        columns: [
                                            {
                                                id: "data22", header: "", width: 50,
                                                template: function (row) {
                                                    if (row.change == 1) {
                                                        return "<span style='cursor:pointer' class='mdi mdi-check-bold webix_button'></span>";
                                                    }
                                                    else {
                                                        return "<span style='cursor:pointer' class='webix_icon'></span>";
                                                    }

                                                }
                                            },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "Order_ID", header: ["Order_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                            { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                            { id: "Pickup_Date", header: [{ text: "Pickup Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, editor: "text" },
                                            /* {
                                                id: "Pickup_Date1", header: [{ text: "Pickup Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, editor: "editdate",
                                                format: webix.Date.dateToStr("%Y-%m-%d")
                                            }, */
                                            { id: "PO_No", header: [{ text: "PO No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Project", header: [{ text: "Project", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Product_Code", header: [{ text: "Product Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Part_No", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Part_Name", header: [{ text: "Part Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "Supplier_Code", header: [{ text: "Supplier Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "Qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 70, css: { "text-align": "center" }, editor: "text", },
                                            { id: "Actual_Qty", header: [{ text: "Picked Qty", css: { "text-align": "center" } }, { content: "numberFilter" }], width: 100, css: { "text-align": "center" }, editor: "text" },
                                            { id: "UM", header: [{ text: "UM", css: { "text-align": "center" } }, { content: "textFilter" }], width: 70, css: { "text-align": "center" }, },
                                            { id: "PO_Line", header: [{ text: "PO Line", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "PO_Release", header: [{ text: "PO Relase", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Refer_ID", header: [{ text: "Refer ID.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            //{ id: "Command", header: [{ text: "Command", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            //{ id: "Creation_Date", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                        ],
                                        // data: [
                                        //     { id: 1, Pickup_Date: '', Pickup_Date1: '' },
                                        //     { id: 2, Pickup_Date: '', Pickup_Date1: '2024-01-01' },
                                        // ],
                                        onClick:
                                        {
                                            "mdi-check-bold": function (e, t) {
                                                var row = this.getItem(t), dataTable = this;
                                                ajax(fd, row, 21, function (json) {
                                                    row.change = 0;
                                                    dataTable.updateItem(t.row, row);
                                                    webix.message({
                                                        type: "success",
                                                        text: "Save Complete",
                                                        expire: 5000
                                                    })
                                                }, null);
                                                //loadData();
                                            },
                                        },
                                        on:
                                        {
                                            "onEditorChange": function (id, value) {
                                                var row = this.getItem(id), dataTable = this;
                                                row.change = 1;
                                                dataTable.updateItem(id.row, row);
                                            },
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