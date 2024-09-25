var header_EditBilling = function () {
    var menuName = "EditBilling_", fd = "TransactionData/" + menuName + "data.php";

    function init() {
        reload_options_customer();
        //refreshAt(00, 00, 0); //Will refresh the page at 00:00
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

    function loadDataTransaction(btn) {
        var obj = ele('fromdatatransaction').getValues();
        ajax(fd, obj, 1, function (json) {
            setTable('dataTransaction', json.data);
        }, null,
            function (json) {
                //ele('find').callEvent("onItemClick", []);
            }, btn);

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

    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_EditBilling",
        body:
        {
            id: "EditBilling_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Edit Billing", type: "header", },
                    {
                        cols: [
                            {
                                view: "form", scroll: false, id: $n('fromdatatransaction'),
                                elements: [
                                    {
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        {},
                                                        vw1('combo', 'Customer_Code', 'Site', {
                                                            required: false,
                                                            suggest: "common/customerMaster.php?type=1",
                                                            width: 150,
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_customer();
                                                                },
                                                            },
                                                        }),
                                                        vw1('text', 'truck_Control_No', 'Truck Control No.', { labelPosition: "top", width: 150, disabled: false, required: false, }),
                                                        vw1('text', 'pus_No', 'Pickup Sheet', { labelPosition: "top", width: 150, disabled: false, required: false, }),
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1('button', 'find', 'Find', {
                                                                    width: 120, css: "webix_primary",
                                                                    icon: "mdi mdi-magnify", type: "icon",
                                                                    tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                    on: {
                                                                        onItemClick: function (id, e) {
                                                                            var obj = ele('fromdatatransaction').getValues();
                                                                            ajax(fd, obj, 1, function (json) {
                                                                                setTable('dataTransaction', json.data);
                                                                            }, null,
                                                                                function (json) {
                                                                                    //ele('find').callEvent("onItemClick", []);
                                                                                },);
                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        },
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1("button", 'clear', "Clear", {
                                                                    width: 100, css: "webix_secondary",
                                                                    icon: "mdi mdi-backspace", type: "icon",
                                                                    tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                    on:
                                                                    {
                                                                        onItemClick: function () {
                                                                            ele('Customer_Code').setValue('');
                                                                            ele('truck_Control_No').setValue('');
                                                                            ele('pus_No').setValue('');
                                                                            loadDataTransaction();
                                                                            ele('dataTransaction').eachColumn(function (id, col) {
                                                                                var filter = this.getFilter(id);
                                                                                if (filter) {
                                                                                    if (filter.setValue) filter.setValue("")
                                                                                    else filter.value = "";
                                                                                }
                                                                            });
                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        },
                                                        {}
                                                    ]
                                                },
                                                { height: 20 },
                                                {
                                                    cols: [
                                                        {
                                                            view: "fieldset", label: "",
                                                            body:
                                                            {
                                                                view: "datatable", id: $n("dataTransaction"), navigation: true, 
                                                                select: false,
                                                                resizeColumn: true, multiselect: true, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 1,
                                                                autoheight: false,
                                                                editable: true,
                                                                editaction: "custom",
                                                                scheme:
                                                                {
                                                                    $change: function (item) {
                                                                        if (item.order_status == 'no plan') {
                                                                            item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                                                        }
                                                                    }
                                                                },
                                                                columns: [
                                                                    {
                                                                        id: "icon_cancel", header: "&nbsp;", width: 30, template: function (row) {
                                                                            if (row.order_status == 'plan') {
                                                                                return "<span style='cursor:pointer; font-size:12px;' class='mdi mdi-cancel' title='ยกเลิกออเดอร์'></span>";
                                                                            }
                                                                            else if (row.order_status == 'no plan') {
                                                                                return "<span style='cursor:pointer; font-size:12px;' class='mdi mdi-check-circle' title='เปิดออเดอร์'></span>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        id: "icon_edit", header: [{ text: "Edit", css: { "text-align": "center" } }], width: 40, template: function (row) {
                                                                            if (row.change == 1) {
                                                                                return "<button class='mdi mdi-check-bold webix_button' title='บันทึกการแก้ไข' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #25a589;'></button>";
                                                                            } else {
                                                                                return '';
                                                                            }
                                                                        }
                                                                    },
                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: "rank", width: 35, sort: "int" },
                                                                    { id: "Part_No", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "Part_Name", header: [{ text: "Part Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                                                    { id: "Project", header: [{ text: "Project", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Actual_Qty", header: [{ text: "Plan", css: { "text-align": "center" } }, { text: "Qty", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "qty_actual", header: [{ text: "Actual", css: { "text-align": "center", "color": "#ff0000" } }, { text: "Qty", css: { "text-align": "center", "color": "#ff0000" } }], width: 60, css: { "text-align": "center", "cursor":"pointer" }, editor: "text", },
                                                                    //{ id: "Package_Qty", header: [{ text: "Package", css: { "text-align": "center" } }, { text: "Qty", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "CBM", header: [{ text: "Plan CBM", css: { "text-align": "center" } }, { text: " CBM", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "cbm_actual", header: [{ text: "Actual CBM", css: { "text-align": "center", "color": "#ff0000" } }, { text: "CBM", css: { "text-align": "center", "color": "#ff0000" } }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "SNP_Per_Pallet", header: [{ text: "SNP/", css: { "text-align": "center" } }, { text: "Pallet", css: { "text-align": "center" } }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "Status_Pickup", header: [{ text: "Activity", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                                                    { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "truck_Control_No_show", header: [{ text: "Truck Control No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                                                    { id: "truckNo_Date", header: [{ text: "Truck Control Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                                                    { id: "status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Route_Code", header: [{ text: "Route Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "pus_No_show", header: [{ text: "Pus No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "planin_date", header: [{ text: "Operation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "planin_time", header: [{ text: "Operation Time", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "PO_No", header: [{ text: "PO No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "Refer_ID", header: [{ text: "Refer ID.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                    // { id: "Truck_Number", header: [{ text: "Truck No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    // { id: "Truck_Type", header: [{ text: "Truck Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Created_By_ID", header: [{ text: "Creation By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Creation_Date", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Creation_Time", header: [{ text: "Creation Time", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Updated_By_ID", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Last_Updated_Date", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                                                    { id: "Last_Updated_Time", header: [{ text: "Last Updated Time", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                                                    { id: "order_status", header: [{ text: "order_status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center", }, hidden: 1 },
                                                                    { id: "change", header: [{ text: "change", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },

                                                                ],
                                                                onClick:
                                                                {
                                                                    "mdi-check-bold": function (e, id) {
                                                                        var row = this.getItem(id), dataTable = this;

                                                                        var check = true;
                                                                        if (isNaN(row.qty_actual)) {
                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: "Actual Qty => กรุณาป้อนข้อมูลเป็นใส่ตัวเลข", callback: function () {
                                                                                    dataTable.editRow(id);
                                                                                }
                                                                            });
                                                                            check = false;
                                                                            return check;
                                                                        }

                                                                        if (isNaN(row.cbm_actual)) {
                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: "Actual CBM => กรุณาป้อนข้อมูลเป็นใส่ตัวเลข", callback: function () {
                                                                                    dataTable.editRow(id);
                                                                                }
                                                                            });
                                                                            check = false;
                                                                            return check;
                                                                        }

                                                                        if (check == true) {
                                                                            $.post(fd, { obj: row, type: 21 })
                                                                                .done(function (data) {
                                                                                    var json = JSON.parse(data);
                                                                                    if (json.ch == 1) {
                                                                                        webix.message({
                                                                                            type: "success",
                                                                                            text: "Save Complete",
                                                                                            expire: 1000
                                                                                        });
                                                                                        row['change'] = 0;
                                                                                        dataTable.refresh(id);
                                                                                        loadDataTransaction();
                                                                                    }
                                                                                    else if (json.ch == 2) {
                                                                                        webix.alert({
                                                                                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                                dataTable.editRow(id);
                                                                                                row['change'] = 1;
                                                                                                dataTable.refresh(id);
                                                                                                loadDataTransaction();
                                                                                            }
                                                                                        });
                                                                                    }
                                                                                    // else {
                                                                                    //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                                    // }
                                                                                });
                                                                        }
                                                                    },
                                                                    "mdi-cancel": function (e, t) {
                                                                        var row = this.getItem(t), datatable = this;
                                                                        msBox('ยกเลิกออเดอร์นี้', function () {
                                                                            $.post(fd, { obj: row, type: 22 })
                                                                                .done(function (data) {
                                                                                    var json = JSON.parse(data);
                                                                                    if (json.ch == 1) {
                                                                                        webix.message({
                                                                                            type: "success",
                                                                                            text: "Save Complete",
                                                                                            expire: 1000
                                                                                        });
                                                                                        loadDataTransaction();
                                                                                    }
                                                                                    else if (json.ch == 2) {
                                                                                        webix.alert({
                                                                                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                                loadDataTransaction();
                                                                                            }
                                                                                        });
                                                                                    }
                                                                                    // else {
                                                                                    //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                                    // }
                                                                                });
                                                                        }, row);
                                                                    },
                                                                    "mdi-check-circle": function (e, t) {
                                                                        var row = this.getItem(t), datatable = this;
                                                                        msBox('เปิดออเดอร์นี้', function () {
                                                                            $.post(fd, { obj: row, type: 23 })
                                                                                .done(function (data) {
                                                                                    var json = JSON.parse(data);
                                                                                    if (json.ch == 1) {
                                                                                        webix.message({
                                                                                            type: "success",
                                                                                            text: "Save Complete",
                                                                                            expire: 1000
                                                                                        });
                                                                                        loadDataTransaction();
                                                                                    }
                                                                                    else if (json.ch == 2) {
                                                                                        webix.alert({
                                                                                            title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                                loadDataTransaction();
                                                                                            }
                                                                                        });
                                                                                    }
                                                                                    // else {
                                                                                    //     webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                                    // }
                                                                                });
                                                                        }, row);
                                                                    },
                                                                },
                                                                on: {
                                                                    "onItemClick": function (id) {
                                                                        var row = this.getItem(id), dataTable = this;
                                                                        //console.log(row.Status_Pickup);
                                                                        if (row.Status_Pickup == 'PICKUP' && row.order_status == 'plan') {
                                                                            dataTable.editStop();
                                                                            dataTable.editRow(id);
                                                                            // if (row.change == 0) {
                                                                            // }
                                                                            // else {
                                                                            //     row.change = 0;
                                                                            //     dataTable.updateItem(id.row, row);
                                                                            // }
                                                                        }



                                                                    },
                                                                    "onAfterEditStop": function (state, editor, ignoreUpdate) {
                                                                        var grid = ele('dataTransaction');
                                                                        if (state.value != state.old) {
                                                                            row_id = editor.row;
                                                                            record = grid.getItem(row_id);
                                                                            record['change'] = 1;
                                                                            grid.refresh(row_id);
                                                                        }
                                                                    }
                                                                }
                                                            },
                                                        },


                                                    ]
                                                },
                                            ]
                                    },
                                ]
                            },
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