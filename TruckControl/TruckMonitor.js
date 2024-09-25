var header_TruckMonitor = function () {
    var menuName = "TruckMonitor_", fd = "TruckControl/" + menuName + "data.php";

    function init() {
        loadDataTransaction();
        refreshAt(0, 0, 0); //Will refresh the page at 00:00pm
        webix.extend($$("TruckMonitor_id"), webix.ProgressBar);
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

    // function show_progress_icon(delay) {
    //     $$("TimestampReport_id").disable();
    //     $$("TimestampReport_id").showProgress({
    //         delay: delay,
    //         hide: true
    //     });
    //     loadData_Bar();
    //     loadData_Total();
    //     setTimeout(function () {
    //         $$("TimestampReport_id").enable();
    //     }, delay);
    // };

    function openNewTab() {
        var temp = window.open(window.location.origin + "/tspk-mr/TruckPlan", "_blank");
    }

    function removeAllFile() {
        webix.ajax("print/doc/removeAll.php").then(function () { })
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
        //removeAllFile();
    }


    // setInterval(function () {
    //     loadDataTransaction();
    // }, 300000);

    function reload_options_driver() {
        var driverList = ele("Driver_Name").getPopup().getList();
        driverList.clearAll();
        driverList.load("common/driverMaster.php?type=2");
    };

    function reload_options_truck() {
        var truckList = ele("Truck").getPopup().getList();
        truckList.clearAll();
        truckList.load("common/truckMaster.php?type=4");
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

    //edit head
    webix.ui(
        {
            view: "window", id: $n("win_edit_head"), modal: 1,
            head: "บันทึกการเดินรถขนส่งสินค้า", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                rows: [
                    {
                        view: "form", scroll: false, id: $n("win_edit_form_head"), width: 900,
                        elements:
                            [
                                {
                                    view: "fieldset", label: "Truck Control No.", body: {
                                        rows: [
                                            {
                                                cols:
                                                    [
                                                        vw1('text', 'transaction_ID', 'transaction_ID', { labelPosition: "top", hidden: 1 }),
                                                        vw1('text', 'truckNo_Date', 'truckNo_Date', { labelPosition: "top", hidden: 1 }),
                                                        vw2('text', 'truck_Control_No_show_update', 'truck_Control_No_show', 'Truck Control No.', { labelPosition: "top", disabled: true, required: false, }),
                                                        vw2('text', 'truck_Control_No_update', 'truck_Control_No', 'Truck Control No.', { labelPosition: "top", disabled: true, required: false, hidden: 1 }),
                                                        vw1('text', 'start_time', 'กำหนดการเริ่มต้น', { labelPosition: "top", disabled: true, required: false, hidden: 1 }),
                                                        vw1('combo', 'Driver_Name', 'Driver Name', {
                                                            labelPosition: "top", yCount: "10", suggest: "common/driverMaster.php?type=1",
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_driver();
                                                                }
                                                            },
                                                        }),

                                                        vw1('combo', 'Truck', 'Truck', {
                                                            labelPosition: "top", Count: "10", suggest: "common/truckMaster.php?type=3",
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_truck();
                                                                }
                                                            },
                                                        }),


                                                        vw1("text", 'actual_start_Time', "เวลาออก (เช่น 08:00)", { required: true, hidden: 1 }),
                                                        vw1('text', 'mile_Start', 'เลขไมล์', { labelPosition: "top", required: true, hidden: 1 }),
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1('button', 'edit_head', 'Update', {
                                                                    width: 120, css: "webix_orange",
                                                                    icon: "mdi mdi-content-save", type: "icon",
                                                                    tooltip: { template: "บันทึกการแก้ไขรายละเอียด", dx: 10, dy: 15 },
                                                                    on: {
                                                                        onItemClick: function () {
                                                                            var obj = ele('win_edit_form_head').getValues();
                                                                            //console.log(obj);
                                                                            webix.confirm(
                                                                                {
                                                                                    title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                    callback: function (res) {
                                                                                        if (res) {
                                                                                            ajax(fd, obj, 42, function (json) {
                                                                                                ele('win_edit_head').hide();
                                                                                                loadDataTransaction();
                                                                                            }, null,
                                                                                                function (json) {
                                                                                                    /* ele('find').callEvent("onItemClick", []); */
                                                                                                });
                                                                                        }
                                                                                    }
                                                                                });

                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        }
                                                    ]
                                            },
                                        ]
                                    }
                                },
                                {
                                    cols:
                                        [
                                            {},
                                            vw1('button', 'save_head', 'Save', {
                                                width: 120, css: "webix_green",
                                                icon: "mdi mdi-content-save", type: "icon",
                                                tooltip: { template: "บันทึกการเดินรถ", dx: 10, dy: 15 },
                                                on: {
                                                    onItemClick: function () {
                                                        var obj = ele('win_edit_form_head').getValues();
                                                        //console.log(obj);
                                                        webix.confirm(
                                                            {
                                                                title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                callback: function (res) {
                                                                    if (res) {
                                                                        ajax(fd, obj, 41, function (json) {
                                                                            ele('win_edit_head').hide();
                                                                            loadDataTransaction();
                                                                        }, null,
                                                                            function (json) {
                                                                                /* ele('find').callEvent("onItemClick", []); */
                                                                            });
                                                                    }
                                                                }
                                                            });

                                                    }
                                                }
                                            }),
                                            vw1('button', 'cancel_edit_head', 'Cancel', {
                                                width: 120, css: "webix_red",
                                                icon: "mdi mdi-cancel", type: "icon",
                                                tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                                on: {
                                                    onItemClick: function () {
                                                        ele('win_edit_head').hide();
                                                    }
                                                }
                                            }),
                                        ]
                                }
                            ],
                        rules:
                        {
                        }
                    },

                ]
            }
        });


    //edit
    webix.ui(
        {
            view: "window", id: $n("win_edit"), modal: 1,
            head: "บันทึกการเดินรถขนส่งสินค้า", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                rows: [
                    {
                        view: "form", scroll: false, id: $n("win_edit_form"), width: 800,
                        elements:
                            [
                                {
                                    view: "fieldset", label: "Pickup Sheet No.", body: {
                                        rows: [
                                            {
                                                cols: [
                                                    vw1('text', 'transaction_Line_ID', 'transaction_Line_ID', { labelPosition: "top", hidden: 1 }),
                                                    vw2('text', 'pus_No_update', 'pus_No', 'Pickup Sheet No.', { labelPosition: "top", disabled: true, required: false, hidden: 1 }),
                                                    vw2('text', 'pus_No_show_update', 'pus_No_show', 'Pickup Sheet No.', { labelPosition: "top", disabled: true, required: false, }),
                                                    vw1('text', 'Supplier_Name_Short', 'Supplier', { labelPosition: "top", disabled: true, required: false, }),
                                                    vw1('text', 'sequence_Stop', 'ลำดับการเดินรถ', { labelPosition: "top", disabled: true, required: false, hidden: 1 }),
                                                    vw1('text', 'mile', 'เลขไมล์', { labelPosition: "top", required: true, hidden: 1 }),
                                                    //vw1("datepicker", 'actual_Arrival__Date', "เวลาเข้าจริง", { timepicker: true, stringResult: true, format: "%Y-%m-%d %H:%i", required: true, width: 140, }),
                                                    //vw1("datepicker", 'actual_Departure_Date', "เวลาออกจริง", { timepicker: true, stringResult: true, format: "%Y-%m-%d %H:%i", required: true, width: 140, }),
                                                ],
                                            },
                                            {
                                                cols: [
                                                    vw1('text', 'planin_time', 'กำหนดการเวลาเข้า', { labelPosition: "top", disabled: true, required: false, }),
                                                    vw1("text", 'actual_in_time', "เวลาเข้า (ตัวอย่าง 08:00)", { required: true, }),
                                                ],
                                            }, {
                                                cols: [
                                                    vw1('text', 'planout_time', 'กำหนดการเวลาออก', { labelPosition: "top", disabled: true, required: false, }),
                                                    vw1("text", 'actual_out_time', "เวลาออก (ตัวอย่าง 08:00)", { required: true, }),
                                                ],
                                            },
                                            {
                                                cols: [
                                                    vw1('text', 'Remark', 'สาเหตุที่ล่าช้า', { labelPosition: "top", required: false, }),
                                                    vw1('text', 'seal1', 'เลขซีล 1', { labelPosition: "top", required: false, hidden: 1 }),
                                                    vw1('text', 'seal2', 'เลขซีล 2', { labelPosition: "top", required: false, hidden: 1 }),
                                                    vw1('text', 'seal3', 'เลขซีล 3', { labelPosition: "top", required: false, hidden: 1 }),
                                                ]
                                            },
                                            {
                                                cols: [
                                                    vw1("text", 'start_load_time', "เวลาเริ่มขึ้นหรือลงของ (ตัวอย่าง 08:00)", { required: false, hidden: 0 }),
                                                    vw1("text", 'end_load_time', "เวลาขึ้นหรือลงของเสร็จ (ตัวอย่าง 08:00)", { required: false, hidden: 0 }),
                                                    {
                                                        rows: [
                                                            {},
                                                            vw1('button', 'edit_picksheet', 'Update', {
                                                                width: 120, css: "webix_orange",
                                                                icon: "mdi mdi-content-save", type: "icon",
                                                                tooltip: { template: "บันทึกการแก้ไขรายละเอียด", dx: 10, dy: 15 },
                                                                on: {
                                                                    onItemClick: function () {
                                                                        var obj = ele('win_edit_form').getValues();
                                                                        //console.log(obj);
                                                                        webix.confirm(
                                                                            {
                                                                                title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                callback: function (res) {
                                                                                    if (res) {
                                                                                        ajax(fd, obj, 44, function (json) {
                                                                                            ele('win_edit').hide();
                                                                                            loadDataTransaction();
                                                                                        }, null,
                                                                                            function (json) {
                                                                                                /* ele('find').callEvent("onItemClick", []); */
                                                                                            });
                                                                                    }
                                                                                }
                                                                            });

                                                                    }
                                                                }
                                                            }),
                                                        ]
                                                    },
                                                    // 
                                                ]
                                            },
                                        ]
                                    },
                                },
                                {
                                    cols:
                                        [
                                            {},
                                            vw1('button', 'save_picksheet', 'Save', {
                                                width: 120, css: "webix_green",
                                                icon: "mdi mdi-content-save", type: "icon",
                                                tooltip: { template: "บันทึกการเดินรถ", dx: 10, dy: 15 },
                                                on: {
                                                    onItemClick: function () {
                                                        var obj = ele('win_edit_form').getValues();
                                                        //console.log(obj);
                                                        webix.confirm(
                                                            {
                                                                title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                callback: function (res) {
                                                                    if (res) {
                                                                        ajax(fd, obj, 43, function (json) {
                                                                            ele('win_edit').hide();
                                                                            loadDataTransaction();
                                                                        }, null,
                                                                            function (json) {
                                                                                /* ele('find').callEvent("onItemClick", []); */
                                                                            });
                                                                    }
                                                                }
                                                            });

                                                    }
                                                }
                                            }),
                                            vw1('button', 'cancel_edit', 'Cancel', {
                                                width: 120, css: "webix_red",
                                                icon: "mdi mdi-cancel", type: "icon",
                                                tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                                on: {
                                                    onItemClick: function () {
                                                        ele('win_edit').hide();
                                                    }
                                                }
                                            }),
                                        ]
                                }
                            ],
                        rules:
                        {
                        }
                    },

                ]
            }
        });


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_TruckMonitor",
        body:
        {
            id: "TruckMonitor_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "TRUCK MONITOR", type: "header", },
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
                                                                onChange: function () {
                                                                    loadDataTransaction();
                                                                },
                                                            },
                                                        }),
                                                        vw1('text', 'truck_Control_No', 'Truck Control No.', { labelPosition: "top", width: 150, disabled: false, required: false, }),
                                                        vw1('text', 'pus_No', 'Pickup Sheet', { labelPosition: "top", width: 150, disabled: false, required: false, }),
                                                        {}
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        {},
                                                        vw1('button', 'find', 'Find', {
                                                            width: 120, css: "webix_primary",
                                                            icon: "mdi mdi-magnify", type: "icon",
                                                            tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                            on: {
                                                                onItemClick: function (id, e) {
                                                                    var obj = ele('fromdatatransaction').getValues();
                                                                    ajax(fd, obj, 2, function (json) {
                                                                        setTable('dataTransaction', json.data);
                                                                    }, null,
                                                                        function (json) {
                                                                            //ele('find').callEvent("onItemClick", []);
                                                                        },);
                                                                }
                                                            }
                                                        }),

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

                                                        vw1('button', 'btn_return', 'Return Plan', {
                                                            width: 120, css: "webix_orange",
                                                            icon: "mdi mdi-replay", type: "icon",
                                                            tooltip: { template: "ดึงแพลนที่ยกเลิกไปแล้วกลับมา", dx: 10, dy: 15 },
                                                            on: {
                                                                onItemClick: function (id, e) {
                                                                    var obj = ele('fromdatatransaction').getValues();
                                                                    webix.confirm(
                                                                        {
                                                                            title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการดึงแพลนกลับมา<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                            callback: function (res) {
                                                                                if (res) {
                                                                                    ajax(fd, obj, 22, function (json) {
                                                                                        ajax(fd, obj, 2, function (json) {
                                                                                            setTable('dataTransaction', json.data);
                                                                                        }, null,
                                                                                            function (json) {
                                                                                                //ele('find').callEvent("onItemClick", []);
                                                                                            },);
                                                                                        //loadDataTransaction();
                                                                                    }, null,
                                                                                        function (json) {
                                                                                            //ele('find').callEvent("onItemClick", []);
                                                                                        },);
                                                                                }
                                                                            }
                                                                        });
                                                                }
                                                            }
                                                        }),
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
                                                                view: "datatable", id: $n("dataTransaction"), navigation: true, select: "row", editaction: "custom",
                                                                resizeColumn: true, multiselect: true, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 7,
                                                                autoheight: false,
                                                                editable: true,
                                                                editaction: "dblclick",
                                                                datafetch: 50, // Number of rows to fetch at a time
                                                                loadahead: 100, // Number of rows to prefetch
                                                                pager: $n("Master_pagerA"),
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
                                                                columns: [
                                                                    {
                                                                        id: $n("cancel"), header: { text: "Cancel Plan", rotate: true, height: 65, css: { "text-align": "center" } }, width: 40, template: function (row) {
                                                                            if (row.Row_No == 1 && row.status != 'CANCEL' && row.tran_status != 'CANCEL') {
                                                                                return "<button class='mdi mdi-cancel webix_button' title='ยกเลิกแพลน' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #ed3755;'></button>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        id: "icon_edit", header: [{ text: "Edit Plan", rotate: true, height: 65, css: { "text-align": "center" } }], width: 40, template: function (row) {
                                                                            if (row.Row_No == 1 && row.status != 'CANCEL' && row.tran_status != 'CANCEL') {
                                                                                return "<button class='mdi mdi-pencil webix_button' title='แก้ไขแพลน' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #f08502;'></button>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        id: "doc", header: { text: "View Document", rotate: true, height: 65, css: { "text-align": "center" } }, width: 45, template: function (row) {
                                                                            if (row.Row_No == 1 && row.status != 'CANCEL' && row.tran_status != 'CANCEL') {
                                                                                return "<button class='mdi mdi-file webix_button' title='ดูเอกสาร' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #556892;'></button>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        id: "icon_start", header: "&nbsp;", width: 40, css: { "text-align": "center" }, template: function (row) {
                                                                            if (row.Row_No == 1 && row.status != 'CANCEL' && row.tran_status != 'CANCEL') {
                                                                                return "<button class='mdi mdi-truck webix_button' title='แก้ไขข้อมูลรถหรือข้อมูลพนักงานขับรถ' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #0C84C8;'></button>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    {
                                                                        id: $n("update"), header: "&nbsp;", width: 40, template: function (row) {
                                                                            if ((row.status == 'IN-TRANSIT' || row.status == 'COMPLETE') && row.status != 'CANCEL' && row.tran_status != 'CANCEL') {
                                                                                return "<button class='mdi mdi-clock-outline webix_button' title='แก้ไขหรือเพิ่มข้อมูลวันเวลาเข้ารับงาน' style='width:22px; height:22px; font-size:12px; color:#ffffff; background-color: #25a589;'></button>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    //{ id: "update", header: "", template: "<button class='mdi mdi-pencil webix_button' style='width:25px; height:20px; color:#ffffff; background-color: #68A4C4;'></button>", width: 50 },
                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: "rank", width: 35, sort: "int" },
                                                                    { id: "truck_Control_No_show", header: [{ text: "Truck Control No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "pus_Date", header: [{ text: "Pickup Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "Route_Code", header: [{ text: "Route Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "start_time", header: [{ text: "กำหนดการเริ่มต้น", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "actual_start_Time", header: [{ text: "เวลาเริ่มต้นจริง", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "mile_Start", header: [{ text: "เลขไมล์เริ่มต้น", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "pus_No_show", header: [{ text: "Pickup Sheet No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },
                                                                    { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 220, css: { "text-align": "center" }, },
                                                                    { id: "sequence_Stop", header: [{ text: "Stop", css: { "text-align": "center" } }, { content: "textFilter" }], width: 70, css: { "text-align": "center" }, },

                                                                    { id: "planin_time", header: [{ text: "กำหนดการเวลาเข้า", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "actual_in_time", header: [{ text: "เวลาเข้าจริง", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "planout_time", header: [{ text: "กำหนดการเวลาออก", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "actual_out_time", header: [{ text: "เวลาออกจริง", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "start_load_time", header: [{ text: "เวลาเริ่มขึ้นหรือลงของ", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                                                    { id: "end_load_time", header: [{ text: "เวลาขึ้นหรือลงของเสร็จ", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },

                                                                    { id: "return_planin_time", header: [{ text: "Plan in(return)", css: { "text-align": "center" } },], width: 110, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "return_planout_time", header: [{ text: "Plan out(return)", css: { "text-align": "center" } },], width: 115, css: { "text-align": "center" }, hidden: 1 },

                                                                    { id: "mile", header: [{ text: "เลขไมล์", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "Remark", header: [{ text: "สาเหตุที่ล่าช้า", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "seal1", header: [{ text: "เลขซีล 1", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "seal2", header: [{ text: "เลขซีล 2", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "seal3", header: [{ text: "เลขซีล 3", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "Truck_Number", header: [{ text: "ทะเบียนรถ", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Truck_Type", header: [{ text: "ประเภทรถ", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "transaction_ID", header: ["transaction_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Route_ID", header: ["Route_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "truck_ID", header: ["truck_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Driver_ID", header: ["Driver_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Supplier_ID", header: ["Supplier_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "transaction_Line_ID", header: ["transaction_Line_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Driver_Name", header: [{ text: "Driver Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "Created_By_ID", header: [{ text: "Created By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Updated_By_ID", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },

                                                                    //{ id: "gps_datetime_connect", header: [{ text: "GPS Updated", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    //{ id: "gps_connection", header: [{ text: "GPS Connection", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, hidden: 0 },
                                                                    //{ id: "Updated_By_ID", header: [{ text: "Updated By", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 1 },

                                                                ],
                                                                onClick:
                                                                {
                                                                    "mdi-clock-outline": function (e, t) {
                                                                        ele('win_edit').show();
                                                                        var row = this.getItem(t);
                                                                        ele('win_edit_form').setValues(row);
                                                                    },
                                                                    "mdi-truck": function (e, t) {
                                                                        ele('win_edit_head').show();
                                                                        var row = this.getItem(t);
                                                                        ele('win_edit_form_head').setValues(row);
                                                                        reload_options_driver();
                                                                        reload_options_truck();
                                                                    },
                                                                    "mdi-file": function (e, t) {
                                                                        var row = this.getItem(t);
                                                                        var data = row.truck_Control_No;
                                                                        var file_name = data.substring(0, 13);

                                                                        $$("TruckMonitor_id").disable();
                                                                        $$("TruckMonitor_id").showProgress({
                                                                            delay: 30000,
                                                                            hide: true
                                                                        });


                                                                        var route_special = row.route_special;
                                                                        var amount_truck = row.amount_truck;

                                                                        //console.log(amount_truck);

                                                                        if (route_special == 'UT') {
                                                                            if (amount_truck == 1) {
                                                                                setTimeout(function () {
                                                                                    webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                                        webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                                            webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                                                webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                                                    var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank')
                                                                                                    $$("TruckMonitor_id").enable();
                                                                                                    $$("TruckMonitor_id").hideProgress();
                                                                                                    webix.ajax("print/doc/removeAll.php?data=" + data).then(function () { });
                                                                                                });
                                                                                            });
                                                                                        });
                                                                                    });
                                                                                }, 0);
                                                                            } else {
                                                                                setTimeout(function () {
                                                                                    webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                                        webix.ajax("print/doc/truck_control_from_blank.php?data=" + data).then(function () {
                                                                                            webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                                                webix.ajax("print/doc/truck_control_from_customer_blank.php?data=" + data).then(function () {
                                                                                                    webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                                                        webix.ajax("print/doc/doc_all_special.php?data=" + data).then(function () {
                                                                                                            var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank')
                                                                                                            $$("TruckMonitor_id").enable();
                                                                                                            $$("TruckMonitor_id").hideProgress();
                                                                                                            webix.ajax("print/doc/removeAllSpecial.php?data=" + data).then(function () { });
                                                                                                        });
                                                                                                    });
                                                                                                });
                                                                                            });
                                                                                        });
                                                                                    });
                                                                                }, 0);
                                                                            }
                                                                        } else {
                                                                            setTimeout(function () {
                                                                                webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                                    webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                                        webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                                            webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                                                var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank')
                                                                                                $$("TruckMonitor_id").enable();
                                                                                                $$("TruckMonitor_id").hideProgress();
                                                                                                webix.ajax("print/doc/removeAll.php?data=" + data).then(function () { });
                                                                                            });
                                                                                        });
                                                                                    });
                                                                                });
                                                                            }, 0);
                                                                        }

                                                                        // setTimeout(function () {
                                                                        //     webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                        //         webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                        //             webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                        //                 webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                        //                     var temp = window.open("print/doc/merge_doc/" + file_name + '.pdf', '_blank')
                                                                        //                     $$("TruckMonitor_id").enable();
                                                                        //                     $$("TruckMonitor_id").hideProgress();
                                                                        //                 });
                                                                        //             });
                                                                        //         });
                                                                        //     });
                                                                        // }, 0);



                                                                        // setTimeout(function () {
                                                                        //     webix.ajax("print/doc/loop_createTruckcontrol.php?data=" + data).then(function () {
                                                                        //         webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                        //             webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                        //                 webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                        //                     var temp = window.open("print/doc/merge_doc/" + file_name + '.pdf', '_blank')
                                                                        //                     $$("TruckMonitor_id").enable();
                                                                        //                     $$("TruckMonitor_id").hideProgress();
                                                                        //                 });
                                                                        //             });
                                                                        //         });
                                                                        //     });
                                                                        // }, 0);
                                                                        // setTimeout(function () {
                                                                        //     webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                        //         webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                        //             webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                        //                 webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                        //                     var temp = window.open("print/doc/merge_doc/" + file_name + '.pdf', '_blank')
                                                                        //                     $$("TruckMonitor_id").enable();
                                                                        //                     $$("TruckMonitor_id").hideProgress();
                                                                        //                 });
                                                                        //             });
                                                                        //         });
                                                                        //     });
                                                                        // }, 0);

                                                                        // setTimeout(function () {
                                                                        //     var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank')
                                                                        //     //console.log(2);
                                                                        //     // temp.addEventListener('load', function () {
                                                                        //     //     webix.ajax("print/doc/deletefile.php?data=" + data).then(function () {console.log(3);});
                                                                        //     // }, false);
                                                                        //     $$("TruckMonitor_id").enable();
                                                                        // }, 5000);
                                                                        // webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                        //     var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank');
                                                                        //     temp.addEventListener('load', function () {
                                                                        //         webix.ajax("print/doc/deletefile.php?data=" + data).then(function () {
                                                                        //         });
                                                                        //     }, false);
                                                                        //     // webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                        //     //     webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                        //     //         var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank');
                                                                        //     //         temp.addEventListener('load', function () {
                                                                        //     //             webix.ajax("print/doc/deletefile.php?data=" + data).then(function () {
                                                                        //     //             });
                                                                        //     //         }, false);
                                                                        //     //     });
                                                                        //     // });
                                                                        // });
                                                                    },
                                                                    "mdi-pencil": function (e, t) {
                                                                        var row = this.getItem(t);
                                                                        //var obj = row.truck_Control_No;
                                                                        msBox('แก้ไข', function () {
                                                                            ajax(fd, row, 21, function (json) {
                                                                                loadDataTransaction();
                                                                                openNewTab();
                                                                                //webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'แก้ไขสำเร็จ', callback: function () { } });
                                                                            }, null,
                                                                                function (json) {
                                                                                });
                                                                        }, row);

                                                                    },
                                                                    "mdi-cancel": function (e, t) {
                                                                        var row = this.getItem(t);
                                                                        //var obj = row.truck_Control_No;
                                                                        msBox('ยกเลิก', function () {
                                                                            ajax(fd, row, 31, function (json) {
                                                                                loadDataTransaction();
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยกเลิกสำเร็จ', callback: function () { } });
                                                                            }, null,
                                                                                function (json) {
                                                                                });
                                                                        }, row);

                                                                    },
                                                                },
                                                                on: {
                                                                    "onItemClick": function (id) {
                                                                        this.editRow(id);
                                                                    }
                                                                }
                                                            },
                                                        },


                                                    ]
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
                                                            size: 500,
                                                            group: 5
                                                        },
                                                        {}
                                                    ]
                                                }

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