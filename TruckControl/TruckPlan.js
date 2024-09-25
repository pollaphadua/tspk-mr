var header_TruckPlan = function () {
    var menuName = "TruckPlan_", fd = "TruckControl/" + menuName + "data.php";

    function init() {
        loadData2();
        ele("formPickupSheet").bind(ele("dataOrder"));
        webix.extend($$("TruckPlan_id"), webix.ProgressBar);
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
        setTimeout(function () {
            window.location.reload(true);
        }, timeout);
    }

    webix.editors.$popup = {
        date: {
            view: "popup",
            body: vw1("text", 'time', "Time", { type: "time", width: 100, }),
        },
    };


    /* ---------- options ---------- */
    function reload_options_route() {
        var routeList = ele("Route_Code").getPopup().getList();
        routeList.clearAll();
        routeList.load("common/routeMaster.php?type=4");
    };

    function reload_options_route_init() {
        var routeList = ele("Route_Code").getPopup().getList();
        routeList.clearAll();
        routeList.load("common/routeMaster.php?type=5");
    };

    function reload_options_route_by_customer(customer) {
        var routeList = ele("Route_Code").getPopup().getList();
        routeList.clearAll();
        routeList.load("common/routeMaster.php?type=6&customer=" + customer);
    };

    function reload_options_driver() {
        var driverList = ele("Driver_Name").getPopup().getList();
        driverList.clearAll();
        driverList.load("common/driverMaster.php?type=2");
    };

    function reload_options_truck() {
        var truckList = ele("Truck").getPopup().getList();
        truckList.clearAll();
        truckList.load("common/truckMaster.php?type=4")
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
    /* ---------- options ---------- */

    function loadFindDataOrder(btn) {
        var obj1 = ele('formdata').getValues();
        var obj2 = ele('form1').getValues();
        var obj = { ...obj1, ...obj2 };
        ajax(fd, obj, 1, function (json) {
            setTable('dataOrder', json.data.header);
        }, null,
            function (json) {

            }, btn);

    };

    function loadData2(btn) {
        ajax(fd, {}, 2, function (json) {
            if (json.data.header.length > 0) {
                reload_options_route_init();
                reload_options_truck();
                reload_options_driver();
                reload_options_customer();

                ele('Route_Code').disable();
                ele('truckNo_Date').disable();
                ele('find').disable();
                ele('Create_TCN').disable();
                ele('start_time').disable();

                ele('form1').setValues(json.data.header[0]);
                setTable('dataOrder', json.data.body);
                ele('Pickup_Date').setValue(json.data.header[0].truckNo_Date);

                var route_special = json.data.header[0].route_special;
                if (route_special == 'UT') {
                    ele('amount_truck').show();
                } else {
                    ele('amount_truck').hide();
                }

                loadDataManageRoute();
                loadDataPickupSheet();


                ele('save').enable();
                ele('cancel').enable();
                ele('btn_update').enable();

                ele('clear').disable();
                ele('Customer_Code').disable();
                ele('btn_upload_plan').enable();
                ele('btn_delete_order').enable();


            }
            else {
                ele('amount_truck').hide();
                ele('Route_Code').setValue('');
                ele('transaction_ID').setValue('');
                ele('truck_Control_No').setValue('');
                ele('truck_Control_No_show').setValue('');
                ele('start_time').setValue('');
                ele('Driver_Name').setValue('');


                ele('Truck').setValue('');
                ele('formdata').setValues('');
                ele('formPickupSheet').setValues('');
                ele('dataOrder').clearAll();
                ele('dataManageRoute').clearAll();
                ele('dataPickupSheet').clearAll();

                ele('Route_Code').enable();
                ele('truckNo_Date').enable();
                ele('start_time').enable();
                ele('Driver_Name').enable();
                ele('find').enable();
                ele('Create_TCN').enable();

                ele('save').disable();
                ele('cancel').disable();
                ele('btn_update').disable();


                ele('clear').enable();
                ele('Customer_Code').enable();
                ele('btn_upload_plan').disable();
                ele('btn_delete_order').disable();


                ele('dataManageRoute').hideColumn("icon_cancel");
                ele('dataManageRoute').hideColumn("line_CBM");
                ele('dataManageRoute').hideColumn("sum_qty");
                ele('dataManageRoute').hideColumn("Weight");
                ele('dataManageRoute').hideColumn("line_Weight");
                ele('dataManageRoute').hideColumn("Sum_Weight");
                ele('dataManageRoute').hideColumn("sequence_Stop");
                ele('dataManageRoute').hideColumn("save");

            }
        }, null,
            function (json) {
            }, btn);
    };

    function loadDataManageRoute(btn) {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 3, function (json) {
            setTable('dataManageRoute', json.data);
            ele('dataManageRoute').showColumn("icon_cancel");
            //ele('dataManageRoute').showColumn("icon_check");
            ele('dataManageRoute').showColumn("line_CBM");
            ele('dataManageRoute').showColumn("sum_qty");
            ele('dataManageRoute').showColumn("Weight");
            ele('dataManageRoute').showColumn("line_Weight");
            //ele('dataManageRoute').showColumn("Sum_Weight");
            ele('dataManageRoute').showColumn("save");
        }, null,
            function (json) {
            }, btn);
    };

    
    function loadDataPickupSheet(btn) {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 4, function (json) {
            setTable('dataPickupSheet', json.data);
        }, null,
            function (json) {

            }, btn);

    };

    function loadDataOrder(btn) {
        var obj1 = ele('formdata').getValues();
        var obj2 = ele('form1').getValues();
        var obj = { ...obj1, ...obj2 };
        ajax(fd, obj, 5, function (json) {
            setTable('dataOrder', json.data);
        }, null,
            function (json) {

            }, btn);

    };




    webix.ui(
        {
            view: "window", id: $n("win_upload"), modal: 1,
            head: "Paste Plan (วางข้อมูลแพลนงาน)", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_upload_form"), width: 500,
                elements:
                    [
                        {
                            cols:
                                [
                                    {
                                        paddingX: 20,
                                        paddingY: 10,
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw1('textarea', 'plan', '', {
                                                            labelAlign: "top",
                                                            height: 250,
                                                            // placeholder: "[*Part_No]   [*Part_Name]   [*SNP]   [*Qty]"
                                                            placeholder: "[*Part_No]    [*Qty]"
                                                        }),
                                                    ],
                                                },
                                            ]
                                    }
                                ]
                        },
                        {
                            cols:
                                [
                                    {},
                                    vw1('button', 'save_plan', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj1 = ele('form1').getValues();
                                                var obj2 = ele('win_upload_form').getValues();
                                                var obj = { ...obj1, ...obj2 };
                                                ele('save_plan').disable();
                                                ele('btn_upload_plan').disable();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ele('win_upload').hide();
                                                                ele('win_upload_form').setValues('');
                                                                $.post(fd, { obj: obj, type: 51 })
                                                                    .done(function (data) {
                                                                        var json = JSON.parse(data);
                                                                        if (json.ch == 1) {
                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                    loadData2();
                                                                                    ele('save_plan').enable();
                                                                                    ele('btn_upload_plan').enable();
                                                                                }
                                                                            });
                                                                        }
                                                                        else {
                                                                            webix.alert({
                                                                                title: "<b>ข้อความจากระบบ</b>", type: "alert-error", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                    ele('save_plan').enable();
                                                                                    ele('btn_upload_plan').enable();
                                                                                }
                                                                            });
                                                                        }
                                                                    })
                                                            } else {
                                                                ele('save_plan').enable();
                                                                ele('btn_upload_plan').enable();
                                                            }
                                                        }
                                                    });
                                            }
                                        }
                                    }),
                                    vw1('button', 'cancel_plan', 'Cancel', {
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_upload').hide();
                                                ele('win_upload_form').setValues('');
                                                ele('save_plan').enable();
                                            }
                                        }
                                    }),
                                    vw1('button', 'btn_clear_plan', 'Clear', {
                                        width: 100, css: "webix_secondary",
                                        icon: "mdi mdi-backspace", type: "icon",
                                        tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_upload_form').setValues('');
                                                ele('save_plan').enable();
                                            }
                                        }
                                    }),
                                    {}
                                ]
                        }
                    ],
                rules:
                {
                }
            }
        });


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_TruckPlan",
        body:
        {
            id: "TruckPlan_id",
            type: "space",
            height: 1000,
            rows:
                [
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1("text", 'transaction_ID', "transaction_ID", { disabled: true, required: false, hidden: 1 }),
                                            vw1("text", 'Truck_Control_Route_ID', "Truck_Control_Route_ID", { disabled: true, required: false, hidden: 1 }),
                                            vw1('text', 'start_time', 'start time', { labelPosition: "top", hidden: 1 }),
                                            vw1('combo', 'Customer_Code', 'Site', {
                                                required: false,
                                                suggest: "common/customerMaster.php?type=1",
                                                width: 200,
                                                on: {
                                                    onBlur: function () {
                                                        this.getList().hide();
                                                    },
                                                    onItemClick: function () {
                                                        reload_options_customer();
                                                        ele('Route_Code').setValue('');
                                                    },
                                                },
                                            }),
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
                                                                ele('form1').setValues('');
                                                                ele('truckNo_Date').setValue(new Date());

                                                                ele('dataManageRoute').clearAll();
                                                                ele('dataOrder').clearAll();
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {},
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'save', 'Save', {
                                                        width: 100, css: "webix_green",
                                                        icon: "mdi mdi-content-save", type: "icon",
                                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                                        hidden: 0,
                                                        on: {
                                                            onItemClick: function (id, e) {
                                                                var obj = ele('form1').getValues();

                                                                var route_special = ele('route_special').getValue();
                                                                var amount_truck = ele('amount_truck').getValue();

                                                                webix.confirm(
                                                                    {
                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                        callback: function (res) {
                                                                            if (res) {
                                                                                ajax(fd, obj, 41, function (json) {
                                                                                    loadData2();
                                                                                    var data = obj.truck_Control_No;
                                                                                    var file_name = data.substring(0, 13);

                                                                                    $$("TruckPlan_id").disable();
                                                                                    $$("TruckPlan_id").showProgress({
                                                                                        delay: 30000,
                                                                                        hide: true
                                                                                    });


                                                                                    if (route_special == 'UT') {
                                                                                        if (amount_truck == 1) {
                                                                                            setTimeout(function () {
                                                                                                webix.ajax("print/doc/truck_control_from.php?data=" + data).then(function () {
                                                                                                    webix.ajax("print/doc/truck_control_from_customer.php?data=" + data).then(function () {
                                                                                                        webix.ajax("print/doc/loop_createPUS.php?data=" + data).then(function () {
                                                                                                            webix.ajax("print/doc/doc_all.php?data=" + data).then(function () {
                                                                                                                var temp = window.open("print/doc/merge_doc/" + data + '.pdf', '_blank')
                                                                                                                $$("TruckPlan_id").enable();
                                                                                                                $$("TruckPlan_id").hideProgress();
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
                                                                                                                        $$("TruckPlan_id").enable();
                                                                                                                        $$("TruckPlan_id").hideProgress();
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
                                                                                                            $$("TruckPlan_id").enable();
                                                                                                            $$("TruckPlan_id").hideProgress();
                                                                                                            webix.ajax("print/doc/removeAll.php?data=" + data).then(function () { });
                                                                                                        });
                                                                                                    });
                                                                                                });
                                                                                            });
                                                                                        }, 0);
                                                                                    }
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
                                            {
                                                rows: [
                                                    {},
                                                    vw1('button', 'cancel', 'Cancel', {
                                                        width: 100, css: "webix_red",
                                                        icon: "mdi mdi-cancel", type: "icon",
                                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                                        hidden: 0,
                                                        on: {
                                                            onItemClick: function (id, e) {
                                                                var obj = ele('form1').getValues();
                                                                //console.log(obj4);
                                                                webix.confirm(
                                                                    {
                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการยกเลิก<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                        callback: function (res) {
                                                                            if (res) {
                                                                                ajax(fd, obj, 33, function (json) {
                                                                                    loadData2();
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
                                    {
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        {
                                                            view: "fieldset", label: "Find Order", body:
                                                            {
                                                                rows: [
                                                                    vw1('combo', 'Route_Code', 'Route Code', {
                                                                        suggest: "common/routeMaster.php?type=3",
                                                                        width: 300,
                                                                        on: {
                                                                            onBlur: function () {
                                                                                this.getList().hide();
                                                                            },
                                                                            onItemClick: function () {
                                                                                var customer = ele('Customer_Code').getValue();
                                                                                if (customer != '') {
                                                                                    reload_options_route_by_customer(customer);
                                                                                }
                                                                                else {
                                                                                    reload_options_route();
                                                                                }
                                                                            },
                                                                        },
                                                                    }),
                                                                    {
                                                                        cols: [
                                                                            vw1("datepicker", 'truckNo_Date', "Pickup Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, }),
                                                                            {
                                                                                rows: [
                                                                                    {},
                                                                                    vw1('button', 'find', 'Find', {
                                                                                        width: 100, css: "webix_primary",
                                                                                        icon: "mdi mdi-magnify", type: "icon",
                                                                                        tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                        on: {
                                                                                            onItemClick: function (id, e) {
                                                                                                var obj1 = ele('formdata').getValues();
                                                                                                var obj2 = ele('form1').getValues();
                                                                                                var obj = { ...obj1, ...obj2 };
                                                                                                ajax(fd, obj, 1, function (json) {
                                                                                                    setTable('dataOrder', json.data.header);
                                                                                                    setTable('dataManageRoute', json.data.body);
                                                                                                    ele('dataManageRoute').hideColumn("icon_cancel");
                                                                                                    ele('dataManageRoute').hideColumn("line_CBM");
                                                                                                    ele('dataManageRoute').hideColumn("sum_qty");
                                                                                                    ele('dataManageRoute').hideColumn("Weight");
                                                                                                    ele('dataManageRoute').hideColumn("line_Weight");
                                                                                                    ele('dataManageRoute').hideColumn("Sum_Weight");
                                                                                                    ele('dataManageRoute').hideColumn("sequence_Stop");
                                                                                                    ele('dataManageRoute').hideColumn("save");
                                                                                                    var dataOrder = ele("dataOrder"), obj = {}, data = [];
                                                                                                    if (dataOrder.count() == 0) {
                                                                                                        webix.alert({
                                                                                                            title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบรายการออเดอร์', callback: function () {
                                                                                                                ele('dataOrder').clearAll();
                                                                                                            }
                                                                                                        });
                                                                                                    } else {
                                                                                                        var data = json.data;
                                                                                                    }

                                                                                                    var route = ele('Route_Code').getValue();
                                                                                                    console.log(route);
                                                                                                    $.post('common/truckMaster.php', { obj: route, type: 7 })
                                                                                                        .done(function (data) {
                                                                                                            var json = JSON.parse(data);
                                                                                                            data = eval('(' + data + ')');
                                                                                                            if (json.ch == 1) {
                                                                                                                reload_options_truck();
                                                                                                                var data1 = json.data;
                                                                                                                ele('Truck').setValue(data1[0].Truck);
                                                                                                            }
                                                                                                        });
                                                                                                    $.post('common/driverMaster.php', { obj: route, type: 3 })
                                                                                                        .done(function (data) {
                                                                                                            var json = JSON.parse(data);
                                                                                                            data = eval('(' + data + ')');
                                                                                                            if (json.ch == 1) {
                                                                                                                reload_options_driver();
                                                                                                                var data1 = json.data;
                                                                                                                ele('Driver_Name').setValue(data1[0].Driver_Name);
                                                                                                            }
                                                                                                        });
                                                                                                }, null,
                                                                                                    function (json) {
                                                                                                        ele('dataOrder').clearAll();
                                                                                                    },);
                                                                                            }
                                                                                        }
                                                                                    }),
                                                                                ]
                                                                            },
                                                                        ]
                                                                    },

                                                                ]
                                                            }
                                                        },
                                                        {
                                                            view: "fieldset", label: "Create Truck Control No.", body:
                                                            {
                                                                rows: [
                                                                    {
                                                                        cols: [
                                                                            vw1('combo', 'Truck', 'Truck', {
                                                                                labelPosition: "top", Count: "10",
                                                                                suggest: "common/truckMaster.php?type=3",
                                                                                on: {
                                                                                    onBlur: function () {
                                                                                        this.getList().hide();
                                                                                    },
                                                                                    onItemClick: function () {
                                                                                        reload_options_truck();
                                                                                    }
                                                                                },
                                                                            }),
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
                                                                            {
                                                                                rows: [
                                                                                    {},
                                                                                    vw1('button', 'btn_update', 'Update', {
                                                                                        css: "webix_orange",
                                                                                        width: 100, icon: "mdi mdi-content-save", type: "icon",
                                                                                        tooltip: { template: "เปลี่ยนแปลงทะเบียนรถ/ชื่อพขร.", dx: 10, dy: 15 },
                                                                                        hidden: 0,
                                                                                        on: {
                                                                                            onItemClick: function (id, e) {
                                                                                                var obj = ele('form1').getValues();
                                                                                                //console.log(obj4);
                                                                                                webix.confirm(
                                                                                                    {
                                                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการเปลี่ยนแปลงข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                                        callback: function (res) {
                                                                                                            if (res) {
                                                                                                                ajax(fd, obj, 23, function (json) {
                                                                                                                    loadData2();
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
                                                                    {
                                                                        cols: [
                                                                            vw1('text', 'Driver_ID', 'Driver_ID', { labelPosition: "top", disabled: true, hidden: 1 }),
                                                                            vw1('text', 'truck_Control_No', 'Truck Control No.', { labelPosition: "top", disabled: true, hidden: 1 }),
                                                                            vw1('text', 'truck_Control_No_show', 'Truck Control No.', { labelPosition: "top", disabled: true, }),
                                                                            vw1('text', 'amount_truck', 'จำนวนรถ', { value: '2', labelPosition: "top", disabled: false, hidden: 1, width: 120 }),
                                                                            vw1('text', 'route_special', 'route_special', { labelPosition: "top", disabled: false, hidden: 1, width: 120 }),
                                                                            {
                                                                                rows: [
                                                                                    {},
                                                                                    vw1('button', 'Create_TCN', 'Create', {
                                                                                        css: "webix_blue",
                                                                                        width: 100, icon: "mdi mdi-plus-circle", type: "icon",
                                                                                        tooltip: { template: "สร้างเอกสาร", dx: 10, dy: 15 },
                                                                                        on: {
                                                                                            onItemClick: function (id, e) {
                                                                                                webix.confirm(
                                                                                                    {
                                                                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                                                                        callback: function (res) {
                                                                                                            if (res) {
                                                                                                                var dataOrder = ele("dataOrder"), obj = {}, data = [];
                                                                                                                if (dataOrder.count() == 0) {
                                                                                                                    webix.alert({
                                                                                                                        title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบรายการออเดอร์', callback: function () {
                                                                                                                            //ele('dataManageRoute').clearAll();
                                                                                                                        }
                                                                                                                    });
                                                                                                                } else {
                                                                                                                    var obj = ele('form1').getValues();
                                                                                                                    ajax(fd, obj, 11, function (json) {
                                                                                                                        loadData2();
                                                                                                                    }, null,
                                                                                                                        function (json) {
                                                                                                                            /* ele('find').callEvent("onItemClick", []); */
                                                                                                                        });
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    });
                                                                                            }
                                                                                        }
                                                                                    }),
                                                                                ]
                                                                            },
                                                                        ]
                                                                    }



                                                                ]
                                                            }
                                                        },
                                                    ]
                                                },
                                                {
                                                    view: "fieldset", label: "Data Route", body:
                                                    {
                                                        cols: [
                                                            {
                                                                view: "datatable", id: $n("dataManageRoute"), navigation: true, select: true,
                                                                resizeColumn: true, autoheight: true, multiselect: true, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 2,
                                                                editable: true,
                                                                editaction: "dblclick",
                                                                navigation: true,
                                                                scrollX: true,
                                                                height: 180,
                                                                datafetch: 50, // Number of rows to fetch at a time
                                                                loadahead: 100, // Number of rows to prefetch
                                                                scheme:
                                                                {
                                                                    $change: function (item) {
                                                                        if (item.Pick == 'N') {
                                                                            item.$css = { "background": "#cfcfcf", "font-weight": "bold", "color": "#9e9e9e" };
                                                                        }

                                                                        if (item.Sum_Weight < 0 && item.Status_Pickup == 'DELIVERY') {
                                                                            item.$css = { "background": "#FF8989", "font-weight": "bold" };
                                                                            ele('dataManageRoute').showColumn("Sum_Weight");
                                                                        }
                                                                        else if (item.Sum_Weight < 0 && item.Status_Pickup == 'PICKUP') {
                                                                            item.$css = { "background": "#ffffb2", "font-weight": "bold" };
                                                                            ele('dataManageRoute').showColumn("Sum_Weight");
                                                                        } else {
                                                                            ele('dataManageRoute').hideColumn("Sum_Weight");
                                                                        }
                                                                    }
                                                                },
                                                                columns: [
                                                                    {
                                                                        id: "icon_cancel", header: "&nbsp;", width: 30, template: function (row) {
                                                                            if (row.Status_Pickup == 'PICKUP' && row.Pick == '') {
                                                                                return "<span style='cursor:pointer' class='mdi mdi-cancel' title='ปิดการใช้งาน Supplier ที่ไม่มีออเดอร์'></span>";
                                                                            }
                                                                            else if (row.Status_Pickup == 'PICKUP' && row.Pick == 'N') {
                                                                                return "<span style='cursor:pointer' class='mdi mdi-check-bold' title='เปิดการใช้งาน Supplier'></span>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }
                                                                    },
                                                                    //{ id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: "rank", width: 35, sort: "int" },
                                                                    { id: "transaction_ID", header: [{ text: "transaction_ID", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 1, },
                                                                    { id: "transaction_Line_ID", header: [{ text: "transaction_Line_ID", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 1, },
                                                                    { id: "line_CBM", header: [{ text: "CBM", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "sum_qty", header: [{ text: "Sum Qty(Pcs.)", css: { "text-align": "center" } }, { text: "(Pcs.)", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "Weight", header: [{ text: "Weight", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "line_Weight", header: [{ text: "Actual", css: { "text-align": "center" } }, { text: "Weight", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "Sum_Weight", header: [{ text: "Overweight", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    {
                                                                        id: "Add_Day", header: [{ text: "Add Day", css: { "text-align": "center" } },], width: 115, css: { "text-align": "center" }, hidden: 1,
                                                                        //editor: "text" 
                                                                    },
                                                                    { id: "Route_ID", header: [{ text: "Route_ID", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 1, },
                                                                    { id: "Supplier_ID", header: [{ text: "Supplier_ID", css: { "text-align": "center" } },], width: 100, css: { "text-align": "center" }, hidden: 1, },
                                                                    { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } },], width: 70, css: { "text-align": "center" }, },
                                                                    { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } },], width: 190, css: { "text-align": "center" }, },
                                                                    { id: "sequence_Stop", header: [{ text: "Stop", css: { "text-align": "center" } },], width: 40, css: { "text-align": "center" }, editor: "", },
                                                                    { id: "planin_time", header: [{ text: "Plan in", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, editor: "text", },
                                                                    { id: "planout_time", header: [{ text: "Plan out", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, editor: "text" },
                                                                    { id: "return_planin_time", header: [{ text: "Plan in(return)", css: { "text-align": "center" } },], width: 110, css: { "text-align": "center" }, editor: "", hidden: 1 },
                                                                    { id: "return_planout_time", header: [{ text: "Plan out(return)", css: { "text-align": "center" } },], width: 115, css: { "text-align": "center" }, editor: "", hidden: 1 },
                                                                    { id: "Status_Pickup", header: [{ text: "Pickup/", css: { "text-align": "center" } }, { text: "Delivery", css: { "text-align": "center" } }], width: 80, css: { "text-align": "center" }, },
                                                                    {
                                                                        id: "save", header: "",
                                                                        template: function (row) {
                                                                            if (row.change == 1) {
                                                                                return "<div class='webix_el_button' align='center' style='padding:2px'><button class='webix_primary webix_button' style='width:50px; height:20px; color:#ffffff; background-color: #25a589;'></i>save</button></div>";
                                                                            }
                                                                            else {
                                                                                return "";
                                                                            }
                                                                        }, width: 80,
                                                                    },

                                                                ],
                                                                on: {
                                                                    "onItemDblClick": function (id) {
                                                                        var row = this.getItem(id);
                                                                        if (row.Pick == '') {
                                                                            this.editRow(id);
                                                                            //row.change = 1;
                                                                        } else {
                                                                            ele("dataManageRoute").editStop();
                                                                            row.change = 0;
                                                                        }
                                                                    },
                                                                    "onEditorChange": function (id, value) {
                                                                        var row = this.getItem(id), dataTable = this;
                                                                        row.change = 1;
                                                                        dataTable.updateItem(id.row, row);
                                                                    },
                                                                },
                                                                onClick:
                                                                {
                                                                    "webix_primary": function (e, t) {
                                                                        var row = this.getItem(t), dataTable = this;
                                                                        var obj1 = ele('form1').getValues();
                                                                        var obj4 = { ...obj1, ...row };
                                                                        ajax(fd, obj4, 22, function (json) {
                                                                            row.change = 0;
                                                                            dataTable.updateItem(t.row, row);
                                                                        }, null,
                                                                            function (json) {
                                                                            });
                                                                    },
                                                                    "mdi-cancel": function (e, t) {
                                                                        var row = this.getItem(t), datatable = this;
                                                                        var obj = row.transaction_Line_ID;
                                                                        msBox('ปิดการใช้งาน<br>Supplier นี้', function () {
                                                                            ajax(fd, obj, 31, function (json) {
                                                                                loadDataManageRoute();
                                                                                loadData2();
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ปิดการใช้งานสำเร็จ', callback: function () { } });
                                                                            }, null,
                                                                                function (json) {
                                                                                });
                                                                        }, row);
                                                                    },
                                                                    "mdi-check-bold": function (e, t) {
                                                                        var row = this.getItem(t), datatable = this;
                                                                        var obj = row.transaction_Line_ID;
                                                                        msBox('เปิดการใช้งาน<br>Supplier นี้', function () {
                                                                            ajax(fd, obj, 21, function (json) {
                                                                                row.change = 0;
                                                                                loadDataManageRoute();
                                                                                loadData2();
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'เปิดการใช้งานสำเร็จ', callback: function () { } });
                                                                            }, null,
                                                                                function (json) {
                                                                                });
                                                                        }, row);
                                                                    },
                                                                },
                                                            },
                                                        ]
                                                    }
                                                },
                                            ]
                                    }
                                ]

                            },

                        ]
                    },
                    {
                        cols: [
                            {
                                view: "form", scroll: false, id: $n('formdata'),
                                elements: [
                                    {
                                        rows:
                                            [
                                                {
                                                    cols: [
                                                        vw1("datepicker", 'Pickup_Date', "Pickup Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, width: 130 }),
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1('button', 'find_order', 'Find', {
                                                                    width: 100, css: "webix_primary",
                                                                    icon: "mdi mdi-magnify", type: "icon",
                                                                    tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                    on: {
                                                                        onItemClick: function (id, e) {
                                                                            loadFindDataOrder();
                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        },
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1("button", 'clear_find_order', "Clear", {
                                                                    width: 100, css: "webix_secondary",
                                                                    icon: "mdi mdi-backspace", type: "icon",
                                                                    tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                                    on:
                                                                    {
                                                                        onItemClick: function () {
                                                                            ele('Pickup_Date').setValue('');
                                                                            loadData2();
                                                                            ele('dataOrder').eachColumn(function (id, col) {
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
                                                        {},
                                                    ]
                                                },
                                                { height: 10 },
                                                {
                                                    cols: [
                                                        {
                                                            view: "fieldset", label: "Table Order",
                                                            body:
                                                            {
                                                                view: "datatable", id: $n("dataOrder"), navigation: true, select: "row", editaction: "custom",
                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 4,
                                                                editable: true,
                                                                editaction: "dblclick",
                                                                navigation: true,
                                                                datafetch: 50, // Number of rows to fetch at a time
                                                                loadahead: 100, // Number of rows to prefetch
                                                                scheme:
                                                                {
                                                                    $change: function (obj) {
                                                                        var css = {};
                                                                        obj.$cellCss = css;
                                                                    }
                                                                },
                                                                columns: [
                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: "rank", width: 30, sort: "int" },
                                                                    {
                                                                        id: "CBM", header: [{ text: "CBM", css: { "text-align": "left" } }], width: 40, css: { "text-align": "left" }, hidden: 0,
                                                                        format: webix.Number.numToStr({
                                                                            groupDelimiter: ",",
                                                                            groupSize: 3,
                                                                            decimalDelimiter: ".",
                                                                            decimalSize: 3
                                                                        })
                                                                    },
                                                                    { id: "Package_Qty", header: [{ text: "Package", css: { "text-align": "center" } }, { text: "Qty", css: { "text-align": "center" } }], width: 55, css: { "text-align": "center" }, hidden: 0, editor: "", format: webix.i18n.numberFormat },
                                                                    {
                                                                        id: "save_qty", header: "", template: "<button class='mdi mdi-plus-circle webix_button' style='width:22px; height:22px; font-size:12px; color:#556892; background-color: #dadee0;'></button>",
                                                                        width: 40, css: { "text-align": "left" },
                                                                    },
                                                                    { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 70, css: { "text-align": "center" }, hidden: 0, sort: "string" },
                                                                    { id: "Part_No", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Part_Name", header: [{ text: "Part Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Sum_Qty", header: [{ text: "Qty(Pcs.)", css: { "text-align": "center", "color": "#ff0000" } },], width: 70, css: { "text-align": "center" }, hidden: 0, editor: "text", },
                                                                    { id: "Project", header: [{ text: "Project", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },
                                                                    { id: "Product_Code", header: [{ text: "Product Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "CBM_Per_Pkg", header: [{ text: "CBM/Pcs.", css: { "text-align": "center" } },], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "WT", header: [{ text: "Weight", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "Actual_Qty", header: [{ text: "Picked", css: { "text-align": "center" } },], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "SNP_Per_Pallet", header: [{ text: "SNP/", css: { "text-align": "center" } }, { text: "Package", css: { "text-align": "center" } }], width: 55, css: { "text-align": "center" }, },
                                                                    { id: "PO_No", header: [{ text: "PO No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, sort: "string" },
                                                                    { id: "Refer_ID", header: [{ text: "Refer ID.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "Dimansion", header: [{ text: "Dimansion", css: { "text-align": "center" } }, { text: "Pallet Size (mm.)", css: { "text-align": "center" } }], width: 120, css: { "text-align": "center" }, hidden: 0, sort: "string" },
                                                                    { id: "Supplier_ID", header: [{ text: "Supplier_ID", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "Pickup_Date", header: [{ text: "Pickup Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                ],
                                                                on: {

                                                                },
                                                                onClick:
                                                                {
                                                                    "mdi-plus-circle": function (e, t) {
                                                                        var row2 = this.getItem(t);
                                                                        var obj1 = ele('form1').getValues();
                                                                        var obj = { ...row2, ...obj1 };

                                                                        var item = this.getItem(t), datatable = this;

                                                                        $.post(fd, { obj: obj, type: 12 })
                                                                            .done(function (data) {
                                                                                var json = JSON.parse(data);
                                                                                if (json.ch == 1) {
                                                                                    if (json.data == 0) {
                                                                                        item.hidden = item.hidden ? false : true;
                                                                                        datatable.updateItem(t, item);

                                                                                        datatable.filter(function (obj) {
                                                                                            return !obj.hidden;
                                                                                        });
                                                                                    } else {
                                                                                        loadDataOrder();
                                                                                    }

                                                                                    setTimeout(function () {
                                                                                        loadDataPickupSheet();
                                                                                    }, 10);

                                                                                    setTimeout(function () {
                                                                                        loadDataManageRoute();
                                                                                    }, 20);
                                                                                }
                                                                                else if (json.ch == 2) {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                            loadDataOrder();
                                                                                        }
                                                                                    });
                                                                                }
                                                                                /* else {
                                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                                } */
                                                                            });
                                                                    },
                                                                },
                                                            },
                                                        },
                                                    ]
                                                }
                                            ]
                                    },
                                ]
                            },
                            { view: "resizer" },
                            {
                                view: "form", scroll: false, id: $n('formPickupSheet'), hidden: false,
                                elements: [
                                    {

                                        rows:
                                            [
                                                { height: 20 },
                                                {
                                                    cols: [
                                                        vw1('button', 'btn_upload_plan', 'Upload Plan', {
                                                            width: 120, css: "webix_blue",
                                                            icon: "mdi mdi-plus-circle", type: "icon",
                                                            tooltip: { template: "เพิ่มข้อมูล", dx: 10, dy: 15 },
                                                            disabled: true,
                                                            on: {
                                                                onItemClick: function () {
                                                                    ele('win_upload').show();
                                                                }
                                                            }
                                                        }),
                                                        {},
                                                    ]
                                                },
                                                { height: 10 },
                                                {
                                                    cols: [
                                                        {
                                                            view: "fieldset", label: "Table Pickup Sheet", body:
                                                            {
                                                                view: "datatable", id: $n("dataPickupSheet"), navigation: true, select: "row", editaction: "custom",
                                                                resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                                                threeState: true, rowLineHeight: 25, rowHeight: 25,
                                                                datatype: "json", headerRowHeight: 25, leftSplit: 3, editable: true,
                                                                navigation: true,
                                                                datafetch: 50, // Number of rows to fetch at a time
                                                                loadahead: 100, // Number of rows to prefetch
                                                                scheme:
                                                                {
                                                                    $change: function (obj) {
                                                                        var css = {};
                                                                        obj.$cellCss = css;
                                                                    }
                                                                },
                                                                columns: [
                                                                    {
                                                                        id: $n("icon_delete"), header: "&nbsp;", width: 50, template: function (row) {
                                                                            //return "<span style='cursor:pointer' class='webix_icon wxi-trash'></span>";
                                                                            return "<button class='mdi mdi-delete webix_button' style='width:25px; height:20px; color:#556892; background-color: #dadee0;'></button>";
                                                                        }
                                                                    },
                                                                    { id: "NO", header: [{ text: "No.", css: { "text-align": "center" } },], css: "rank", width: 35, sort: "int" },
                                                                    { id: "transaction_Line_ID", header: ["transaction_Line_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "transaction_stop_ID", header: ["transaction_stop_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Order_ID", header: ["Order_ID", { content: "textFilter" }], width: 100, hidden: 1 },
                                                                    { id: "Project", header: [{ text: "Project", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, },
                                                                    { id: "Part_No", header: [{ text: "Part No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                                                    { id: "Part_Name", header: [{ text: "Part Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                                                    { id: "Product_Code", header: [{ text: "Product Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Qty", header: [{ text: "Qty", css: { "text-align": "center" } }, { content: "textFilter" }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "CBM", header: [{ text: "CBM", css: { "text-align": "center" } }, { content: "textFilter" }], width: 60, css: { "text-align": "center" }, },
                                                                    { id: "Package_Qty", header: [{ text: "Package", css: { "text-align": "center" } }, { text: "Qty", css: { "text-align": "center" } }], width: 55, css: { "text-align": "center" }, hidden: 0, editor: "", format: webix.i18n.numberFormat },
                                                                    { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 0 },
                                                                    { id: "plan_Arrival_Date", header: [{ text: "Arrival Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "plan_Departure_Date", header: [{ text: "Departure Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, hidden: 1 },
                                                                    { id: "PO_No", header: [{ text: "PO No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                    { id: "Refer_ID", header: [{ text: "Refer ID.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center", }, hidden: 0 },
                                                                    //{ id: "pus_No", header: [{ text: "pus No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                                                ],
                                                                on: {
                                                                    onCheck: function (rowId, colId, state) {
                                                                        var obj = ele('form1').getValues();
                                                                        if (obj.truck_Control_No == '') {
                                                                            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ยังไม่มี Truck Control No.', callback: function () { } });
                                                                        }
                                                                    },
                                                                    "onItemClick": function (id) {
                                                                        this.editRow(id);
                                                                    }
                                                                },
                                                                onClick:
                                                                {
                                                                    "mdi-delete": function (e, t) {
                                                                        var row = this.getItem(t);
                                                                        var obj = row.transaction_stop_ID;
                                                                        var item = this.getItem(t), datatable = this;

                                                                        $.post(fd, { obj: obj, type: 32 })
                                                                            .done(function (data) {
                                                                                var json = JSON.parse(data);
                                                                                if (json.ch == 1) {

                                                                                    item.hidden = item.hidden ? false : true;
                                                                                    datatable.updateItem(t, item);

                                                                                    datatable.filter(function (obj) {
                                                                                        return !obj.hidden;
                                                                                    });

                                                                                    setTimeout(function () {
                                                                                        loadDataOrder();
                                                                                    }, 10);

                                                                                    setTimeout(function () {
                                                                                        loadDataManageRoute();
                                                                                    }, 20);
                                                                                }
                                                                                else if (json.ch == 2) {
                                                                                    webix.alert({
                                                                                        title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                            loadDataPickupSheet();
                                                                                        }
                                                                                    });
                                                                                }
                                                                                /* else {
                                                                                    webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.data, callback: function () { window.open("login.php", "_self"); } });
                                                                                } */
                                                                            });
                                                                    },
                                                                },
                                                            },
                                                        },
                                                    ]
                                                }
                                            ]
                                    },
                                ]
                            },
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