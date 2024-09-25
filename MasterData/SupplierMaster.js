var header_SupplierMaster = function () {
    var menuName = "SupplierMaster_", fd = "MasterData/" + menuName + "data.php";

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
                saveAs(e.data, 'tspkmr_supplier_master' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
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

    //add
    webix.ui(
        {
            view: "window", id: $n("win_add"), modal: 1,
            head: "Add (เพิ่มข้อมูล)", top: 50, position: "center", css: "webix_win_head",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_add_form"), width: 570,
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
                                                        vw1('text', 'Supplier_Code', 'Supplier Code', { labelPosition: "top", width: 250, }),
                                                        vw1('text', 'Supplier_Name_Short', 'Supplier', { labelPosition: "top", width: 250, }),
                                                    ],
                                                }, {
                                                    cols: [
                                                        vw1('text', 'Supplier_Name', 'Supplier Name', { labelPosition: "top", width: 500, }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'Province', 'Province', { labelPosition: "top", width: 250, }),
                                                        vw1('text', 'Sub_Zone', 'Sub Zone', { labelPosition: "top", width: 250, }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'GPS', 'GPS', { labelPosition: "top", width: 500, }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'Address', 'Address', { labelPosition: "top", width: 500 }),
                                                    ],
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
                                                                },
                                                            },
                                                        }),
                                                        {}
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
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {
                                                                ajax(fd, obj, 11, function (json) {
                                                                    loadData();
                                                                    ele('win_add').hide();
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
                                                ele('Supplier_Code').setValue('');
                                                ele('Supplier_Name').setValue('');
                                                ele('Province').setValue('');
                                                ele('Sub_Zone').setValue('');
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
                view: "form", scroll: false, id: $n("win_edit_form"), width: 600,
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
                                                        vw1('text', 'Supplier_ID', 'Supplier ID', { labelPosition: "top", hidden: 1 }),
                                                        vw2('text', 'Supplier_Code_edit', 'Supplier_Code', 'Supplier Code', { labelPosition: "top", disabled: false, width: 250, }),
                                                        vw2('text', 'Supplier_Name_Short_edit', 'Supplier_Name_Short', 'Supplier', { labelPosition: "top", disabled: false, width: 250, }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Supplier_Name_edit', 'Supplier_Name', 'Supplier Name', { labelPosition: "top", width: 500, }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Province_edit', 'Province', 'Province', { labelPosition: "top", width: 250 }),
                                                        vw2('text', 'Sub_Zone_edit', 'Sub_Zone', 'Sub Zone', { labelPosition: "top", width: 250 }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'GPS_edit', 'GPS', 'GPS', { labelPosition: "top", width: 500, }),
                                                    ],
                                                }, {
                                                    cols: [
                                                        vw2('text', 'Address_edit', 'Address', 'Address', { labelPosition: "top", width: 500 }),
                                                    ],
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
        id: "header_SupplierMaster",
        body:
        {
            id: "SupplierMaster_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "SUPPLIER MASTER", type: "header" },
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
                                                    window.location.href = 'MasterData/template_upload/template_upload_supplier_master.xlsx';
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
                                        //pager: "page_Supplier",
                                        //pager: $n("Master_pagerA"),
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
                                                    //return "<button class='mdi mdi-pencil webix_button' style='width:25px; height:20px; color:#ffffff; background-color: #68A4C4;'></button>";
                                                }
                                            },
                                            { id: "NO", header: "No.", css: "rank", width: 50, sort: "int" },
                                            { id: "Customer_Code", header: [{ text: "Site", css: { "text-align": "center" } }, { content: "textFilter" }], width: 80, css: { "text-align": "center" }, hidden: 0 },
                                            { id: "Supplier_Code", header: [{ text: "Supplier Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name_Short", header: [{ text: "Supplier", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Supplier_Name", header: [{ text: "Supplier Name", css: { "text-align": "center" } }, { content: "textFilter" }], width: 300, css: { "text-align": "center" }, },
                                            { id: "Province", header: [{ text: "Province", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Sub_Zone", header: [{ text: "Sub Zone", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Address", header: [{ text: "Address", css: { "text-align": "center" } }, { content: "textFilter" }], width: 500, css: { "text-align": "left" }, },
                                            { id: "GPS", header: [{ text: "GPS", css: { "text-align": "center" } }, { content: "textFilter" }], width: 300, css: { "text-align": "center" }, },
                                            { id: "Status", header: [{ text: "Status", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Creation_DateTime", header: [{ text: "Creation Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 120, css: { "text-align": "center" }, },
                                            { id: "Last_Updated_DateTime", header: [{ text: "Last Updated Date", css: { "text-align": "center" } }, { content: "textFilter" }], width: 140, css: { "text-align": "center" }, },
                                        ],
                                        onClick:
                                        {
                                            "mdi-pencil": function (e, t) {
                                                ele('win_edit').show();
                                                var row = this.getItem(t);
                                                ele('win_edit_form').setValues(row);
                                                reload_options_customer();
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
                            // {
                            //     type: "wide",
                            //     cols:
                            //         [
                            //             {},
                            //             {
                            //                 view: "pager", id: $n("Master_pagerA"),
                            //                 template: function (data, common) {
                            //                     var start = data.page * data.size
                            //                         , end = start + data.size;
                            //                     if (data.count == 0) start = 0;
                            //                     else start += 1;
                            //                     if (end >= data.count) end = data.count;
                            //                     var html = "<b>showing " + (start) + " - " + end + " total " + data.count + " </b>";
                            //                     return common.first() + common.prev() + " " + html + " " + common.next() + common.last();
                            //                 },
                            //                 size: 50,
                            //                 group: 5
                            //             },
                            //             {}
                            //         ]
                            // }
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