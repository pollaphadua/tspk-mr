var header_RouteMaster = function () {
    var menuName = "RouteMaster_", fd = "MasterData/" + menuName + "data.php";

    function init() {
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

    function setTable(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
        ele(tableName).filterByAll();
    };


    function loadData(btn) {
        ajax(fd, {}, 1, function (json) {
            setTable('dataT1', json.data);
        }, null,
            function (json) {
                //ele('find').callEvent("onItemClick", []);
            }, btn);

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
                saveAs(e.data, 'tspkmr_route_master' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
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
    };

    function reload_options_supplier() {
        var supplierList = ele("Supplier").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=4");
    };

    function reload_options_truck() {
        var truckList = ele("Truck").getPopup().getList();
        truckList.clearAll();
        truckList.load("common/truckMaster.php?type=4");
    };

    //add
    webix.ui(
        {
            view: "window", id: $n("win_add"), modal: 1,
            head: "Add (เพิ่มข้อมูล)", top: 50, position: "center", css: "webix_win_head",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_add_form"), width: 1000,
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
                                                        vw1('text', 'Route_ID', 'Route_ID', { labelPosition: "top", hidden: 1 }),
                                                        vw1('text', 'Route_Code', 'Route Code', { labelPosition: "top", }),
                                                        vw1('combo', 'Truck', 'Truck', {
                                                            labelPosition: "top", yCount: "10",
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
                                                        vw1('richselect', 'Delivery_Method', 'Delivery Method', {
                                                            labelPosition: "top",
                                                            value: 'Direct', options: [
                                                                { id: 'Direct', value: "Direct" },
                                                                { id: 'Combine', value: "Combine" },
                                                            ]
                                                        }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('combo', 'Supplier', 'Supplier', {
                                                            suggest: "common/supplierMaster.php?type=3",
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_supplier();
                                                                }
                                                            },
                                                        }),
                                                        vw1('richselect', 'Delivery_Plant', 'Delivery Plant', {
                                                            labelPosition: "top",
                                                            value: '', options: [
                                                                { id: 'TSPK-C', value: "TSPK-C" },
                                                                { id: 'TSPKK', value: "TSPKK" },
                                                                { id: 'TSPK-BP', value: "TSPK-BP" },
                                                            ]
                                                        }),
                                                        vw1('text', 'Vol', 'Vol', { labelPosition: "top", }),
                                                        vw1('text', 'Weight', 'Weight', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'start_time', 'Start', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw1('text', 'planin_time', 'Plan in', { labelPosition: "top", tooltip: "(เช่น 08:00)" }),
                                                        vw1('text', 'planout_time', 'Plan out', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'return_planin_time', 'Plan in(return)', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw1('text', 'return_planout_time', 'Plan out(return)', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw1('text', 'load_unload_time', 'Load/Unload', { labelPosition: "top", tooltip: "(จำนวนเวลาที่ใช้ในการขึ้นหรือลงของ เช่น 30 นาที ใส่เป็น 00:30)" }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('richselect', 'Status_Pickup', 'Pick Up/Delivery', {
                                                            labelPosition: "top",
                                                            value: 'PICKUP', options: [
                                                                { id: 'PICKUP', value: "PICKUP" },
                                                                { id: 'DELIVERY', value: "DELIVERY" },
                                                            ]
                                                        }),
                                                        vw1('text', 'Distance', 'Distance', { labelPosition: "top", }),
                                                        vw1('text', 'Add_Day', 'Add Day', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('combo', 'Customer_Code', 'Site', {
                                                            suggest: "common/customerMaster.php?type=1",
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_customer();
                                                                }
                                                            },
                                                        }),
                                                        {}, {}
                                                    ]
                                                }
                                            ]
                                    }
                                ]
                        },
                        {
                            cols:
                                [
                                    {},
                                    vw1('button', 'save', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj = ele('win_add_form').getValues();

                                                //console.log(obj);
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 11, function (json) {
                                                                    loadData();
                                                                    ele('win_add').hide();
                                                                    //ele('win_add_form').setValues('');
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
                                    vw1('button', 'cancel', 'Cancel', {
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                ele('win_add').hide();
                                                //ele('win_add_form').setValues('');
                                                // ele('Supplier_Code').setValue('');
                                                // ele('Supplier_Name').setValue('');
                                                // ele('Province').setValue('');
                                                //ele('Delivery_Plant').setValue('TSPK-C');
                                            }
                                        }
                                    }),
                                ]
                        }
                    ],
                rules:
                {
                }
            }
        });

    //edit
    webix.ui(
        {
            view: "window", id: $n("win_edit"), modal: 1,
            head: "Edit (แก้ไขข้อมูล)", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_edit_form"), width: 1000,
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
                                                        vw2('text', 'Route_ID_edit', 'Route_ID', 'Route_ID', { labelPosition: "top", hidden: 1 }),
                                                        vw2('text', 'Route_Code_edit', 'Route_Code', 'Route Code', { labelPosition: "top", }),
                                                        vw2('text', 'Truck_edit', 'Truck', 'Truck', {
                                                            labelPosition: "top", yCount: "10",
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
                                                        vw2('richselect', 'Delivery_Method_edit', 'Delivery_Method', 'Delivery Method', {
                                                            labelPosition: "top",
                                                            value: 'Direct', options: [
                                                                { id: 'Direct', value: "Direct" },
                                                                { id: 'Combine', value: "Combine" },
                                                            ]
                                                        }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Supplier_edit', 'Supplier', 'Supplier', { suggest: "common/supplierMaster.php?type=3", }),
                                                        vw2('richselect', 'Delivery_Plant_edit', 'Delivery_Plant', 'Delivery Plant', {
                                                            labelPosition: "top",
                                                            value: '', options: [
                                                                { id: 'TSPK-C', value: "TSPK-C" },
                                                                { id: 'TSPKK', value: "TSPKK" },
                                                                { id: 'TSPK-BP', value: "TSPK-BP" },
                                                            ]
                                                        }),
                                                        vw2('text', 'Vol_edit', 'Vol', 'Vol', { labelPosition: "top", }),
                                                        vw2('text', 'Weight_edit', 'Weight', 'Weight', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'start_time_edit', 'start_time', 'Start', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw2('text', 'planin_time_edit', 'planin_time', 'Plan in', { labelPosition: "top", tooltip: "(เช่น 08:00)" }),
                                                        vw2('text', 'planout_time_edit', 'planout_time', 'Plan out', { labelPosition: "top", tooltip: "(เช่น 08:00)" }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'return_planin_time_edit', 'return_planin_time', 'Plan in(return)', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw2('text', 'return_planout_time_edit', 'return_planout_time', 'Plan out(return)', { labelPosition: "top", tooltip: "(เช่น 08:00)", required: false }),
                                                        vw2('text', 'load_unload_time_edit', 'load_unload_time', 'Load/Unload', { labelPosition: "top", tooltip: "(จำนวนเวลาที่ใช้ในการขึ้นหรือลงของ เช่น 30 นาที ใส่เป็น 00:30)" }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('richselect', 'Status_Pickup_edit', 'Status_Pickup', 'Pick Up/Delivery', {
                                                            labelPosition: "top",
                                                            value: 'PICKUP', options: [
                                                                { id: 'PICKUP', value: "PICKUP" },
                                                                { id: 'DELIVERY', value: "DELIVERY" },
                                                            ]
                                                        }),
                                                        vw2('text', 'Distance_edit', 'Distance', 'Distance', { labelPosition: "top", }),
                                                        vw2('text', 'Add_Day_edit', 'Add_Day', 'Add Day', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Customer_Code_edit', 'Customer_Code', 'Site', {
                                                            suggest: "common/customerMaster.php?type=1",
                                                            on: {
                                                                onBlur: function () {
                                                                    this.getList().hide();
                                                                },
                                                                onItemClick: function () {
                                                                    reload_options_customer();
                                                                }
                                                            },
                                                        }),
                                                        vw2('richselect', 'Status_edit', 'Status', 'Status', {
                                                            labelPosition: "top",
                                                            value: 'ACTIVE', options: [
                                                                { id: 'ACTIVE', value: "ACTIVE" },
                                                                { id: 'INACTIVE', value: "INACTIVE" },
                                                            ]
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
                                    vw1('button', 'edit', 'Save', {
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        on: {
                                            onItemClick: function () {
                                                var obj = ele('win_edit_form').getValues();
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 21, function (json) {
                                                                    ele('win_edit').hide();
                                                                    loadData();
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
            }
        });


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_RouteMaster",
        body:
        {
            id: "RouteMaster_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "ROUTE MASTER", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                cols:
                                    [
                                        vw1('button', 'add', 'Add', {
                                            width: 120, css: "webix_blue",
                                            icon: "mdi mdi-plus-circle", type: "icon",
                                            tooltip: { template: "เพิ่มข้อมูล", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function () {
                                                    ele('win_add').show();
                                                }
                                            }
                                        }),
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
                                        vw1('button', 'btn_export_template', 'Template Upload', {
                                            width: 150, css: "webix_secondary",
                                            icon: "mdi mdi-download", type: "icon",
                                            tooltip: { template: "ตัวอย่างเทมเพลตที่ใช้ในการอัพโหลด", dx: 10, dy: 15 },
                                            on: {
                                                onItemClick: function () {
                                                    window.location.href = 'MasterData/template_upload/template_upload_route_master.xlsx';
                                                }
                                            },
                                        }),
                                        vw1("uploader", 'btn_upload_file', "Upload Data", {
                                            width: 120, css: "webix_blue",
                                            icon: "mdi mdi-upload", type: "icon",
                                            tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                            on:
                                            {
                                                onBeforeFileAdd: function (file) {
                                                    var type = file.type.toLowerCase();
                                                    if (type == "csv" || type == "xlsx" || type == "xls") {

                                                    }
                                                    else {
                                                        webix.alert({ title: "<b>ข้อความจากระบบ</b>", text: "รองรับ CSV ,XLS ,XLSX เท่านั้น", type: 'alert-error' });
                                                        return false;
                                                    }
                                                    //ele("btn_upload_file").disable();
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
                                                        url: fd + '?type=41',
                                                        data: formData,
                                                        success: function (data) {
                                                            //ele("btn_upload_file").enable();
                                                            loadData();
                                                            var json = JSON.parse(data);
                                                            webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: json.mms, callback: function () { } });
                                                        }
                                                    });
                                                },
                                            },
                                        }),
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
                                cols: [
                                    {
                                        view: "datatable", id: $n("dataT1"), navigation: true, select: "row", editaction: "custom",
                                        resizeColumn: true, autoheight: false, multiselect: true, hover: "myhover",
                                        threeState: true, rowLineHeight: 25, rowHeight: 25,
                                        datatype: "json", headerRowHeight: 25, leftSplit: 5, editable: true,
                                        scheme:
                                        {
                                            $change: function (obj) {
                                                var css = {};
                                                obj.$cellCss = css;
                                            }
                                        },
                                        columns: [
                                            {
                                                id: "icon_edit", header: "&nbsp;", width: 50, template: function (row) {
                                                    return "<span style='cursor:pointer; font-size:16px;' class='mdi mdi-pencil'></span>";
                                                    // return "<button class='mdi mdi-pencil webix_button' style='width:25px; height:20px; color:#ffffff; background-color: #68A4C4;'></button>";
                                                }
                                            },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                            { id: "Route_ID", header: [{ text: "Route_ID", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, hidden: 1, },
                                            { id: "Route_Code", header: [{ text: "Route Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Truck_Number", header: [{ text: "Truck No.", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Truck_Type", header: [{ text: "Truck Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Delivery_Method", header: [{ text: "Delivery Method", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, css: { "text-align": "center" }, },
                                            { id: "Province", header: [{ text: "Province", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, hidden: 1 },
                                            { id: "Sub_Zone", header: [{ text: "Sub Zone", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Model", header: [{ text: "Model", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Delivery_Plant", header: [{ text: "Delivery Plant", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Vol", header: [{ text: "Vol.(m3)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Weight", header: [{ text: "Weight(Kg.)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "start_time", header: [{ text: "Start", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "planin_time", header: [{ text: "Plan in", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "planout_time", header: [{ text: "Plan Out", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "return_planin_time", header: [{ text: "Plan in(return)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "return_planout_time", header: [{ text: "Plan Out(return)", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "load_unload_time", header: [{ text: "Load/Unload", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Status_Pickup", header: [{ text: "Pickp up/Delivery", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Distance", header: [{ text: "Distance", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Add_Day", header: [{ text: "Add Day", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                        ],
                                        onClick:
                                        {
                                            // "mdi-pencil": function (e, t) {
                                            //     var row = this.getItem(t);
                                            //     var obj = row.Route_Header_ID;
                                            //     msBox('แก้ไข', function () {
                                            //         ajax(fd, obj, 21, function (json) {
                                            //             loadData();
                                            //         }, null,
                                            //             function (json) {
                                            //             });
                                            //     }, row);
                                            // },
                                            "mdi-pencil": function (e, t) {
                                                ele('win_edit').show();
                                                var row = this.getItem(t);
                                                ele('win_edit_form').setValues(row);
                                                reload_options_customer();
                                                reload_options_supplier();
                                                reload_options_truck();
                                            },
                                            "mdi-delete": function (e, t) {
                                                var row = this.getItem(t);
                                                var obj = row.Route_Pre_ID;
                                                msBox('ลบ', function () {
                                                    ajax(fd, obj, 31, function (json) {
                                                        loadData();
                                                    }, null,
                                                        function (json) {
                                                        });
                                                }, row);
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