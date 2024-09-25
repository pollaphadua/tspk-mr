var header_PackageMaster = function () {
    var menuName = "PackageMaster_", fd = "MasterData/" + menuName + "data.php";

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
                saveAs(e.data, 'tspkmr_package_master' + dayjs().format('YYYYMMDDHHmmss') + ".xlsx");
                btn.enable();
                webix.message({ expire: 7000, text: "Export สำเร็จ" });
            }, false);
            worker.postMessage({ 'cmd': 'start', 'msg': data });
        }
        else { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: "ไม่พบข้อมูลในตาราง", callback: function () { } }); }
    };

    //add
    webix.ui(
        {
            view: "window", id: $n("win_add"), modal: 1,
            head: "Add (เพิ่มข้อมูล)", top: 50, position: "center",
            close: true, move: true,
            body:
            {
                view: "form", scroll: false, id: $n("win_add_form"), width: 500,
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
                                                        vw1('text', 'Package_Code', 'Package Code', { labelPosition: "top", hidden: 1 }),
                                                        vw1('text', 'Package_Description', 'Package Description', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('richselect', 'Package_Type', 'Package Type', {
                                                            labelPosition: "top",
                                                            value: 'Box', options: [
                                                                { id: 'Box', value: "Box" },
                                                                { id: 'Pallet', value: "Pallet" },
                                                                { id: 'Rack', value: "Rack" },
                                                            ]
                                                        }),
                                                        vw1('richselect', 'Packaging', 'Packaging', {
                                                            labelPosition: "top",
                                                            value: 'PTB', options: [
                                                                { id: 'PTB', value: "PTB-กล่องพลาสติก" },
                                                                { id: 'STP', value: "STP-พาเลทเหล็ก" },
                                                                { id: 'STD', value: "STD-แร็ก" },
                                                            ]
                                                        }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'Width_Pallet_Size', 'Width', { labelPosition: "top" }),
                                                        vw1('text', 'Length_Pallet_Size', 'Length', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw1('text', 'Height_Pallet_Size', 'Height', { labelPosition: "top" }),
                                                        {}
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
                                                                    setTable('dataT1', json.data);
                                                                    ele('win_add').hide();
                                                                    ele('Package_Code').setValue('');
                                                                    ele('Package_Description').setValue('');
                                                                    ele('Width_Pallet_Size').setValue('');
                                                                    ele('Height_Pallet_Size').setValue('');
                                                                    ele('Length_Pallet_Size').setValue('');
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
                                                ele('Package_Code').setValue('');
                                                ele('Package_Description').setValue('');
                                                ele('Width_Pallet_Size').setValue('');
                                                ele('Height_Pallet_Size').setValue('');
                                                ele('Length_Pallet_Size').setValue('');
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
                                                        vw2('text', 'Package_ID_edit', 'Package_ID', 'Package ID', { labelPosition: "top", hidden: 1 }),
                                                        vw2('text', 'Package_Code_edit', 'Package_Code', 'Package Code', { labelPosition: "top", disabled: true, }),
                                                        vw2('text', 'Package_Description_edit', 'Package_Description', 'Description', { labelPosition: "top", }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('richselect', 'Package_Type_edit', 'Package_Type', 'Package Type', {
                                                            labelPosition: "top",
                                                            value: 'Box', options: [
                                                                { id: 'Box', value: "Box" },
                                                                { id: 'Pallet', value: "Pallet" },
                                                                { id: 'Rack', value: "Rack" },
                                                            ]
                                                        }),
                                                        vw2('richselect', 'Packaging_edit', 'Packaging', 'Packaging', {
                                                            labelPosition: "top",
                                                            value: 'PTB', options: [
                                                                { id: 'PTB', value: "PTB-กล่องพลาสติก" },
                                                                { id: 'STP', value: "STP-พาเลทเหล็ก" },
                                                                { id: 'STD', value: "STD-แร็ก" },
                                                            ]
                                                        }),
                                                    ]
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Width_Pallet_Size_edit', 'Width_Pallet_Size', 'Width', { labelPosition: "top" }),
                                                        vw2('text', 'Length_Pallet_Size_edit', 'Length_Pallet_Size', 'Length', { labelPosition: "top" }),
                                                    ],
                                                },
                                                {
                                                    cols: [
                                                        vw2('text', 'Height_Pallet_Size_edit', 'Height_Pallet_Size', 'Height', { labelPosition: "top" }),
                                                        vw2('richselect', 'Status_edit', 'Status', 'Status', {
                                                            labelPosition: "top",
                                                            value: 'ACTIVE', options: [
                                                                { id: 'ACTIVE', value: "ACTIVE" },
                                                                { id: 'INACTIVE', value: "INACTIVE" },
                                                            ]
                                                        }),
                                                        //{}
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
        id: "header_PackageMaster",
        body:
        {
            id: "PackageMaster_id",
            type: "space",
            rows:
                [
                    { view: "template", template: "PACKAGE MASTER", type: "header" },
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
                                        datatype: "json", headerRowHeight: 25, leftSplit: 3, editable: true,
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
                                            { id: "Package_ID", header: [{ text: "Package_ID", css: { "text-align": "center" } }, { content: "textFilter" }], width: 200, hidden: 1, },
                                            { id: "Package_Code", header: [{ text: "Package Code", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Package_Description", header: [{ text: "Description", css: { "text-align": "center" } }, { content: "textFilter" }], width: 150, css: { "text-align": "center" }, },
                                            { id: "Package_Type", header: [{ text: "Package Type", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Packaging", header: [{ text: "Packaging", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Width_Pallet_Size", header: [{ text: "Width", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Length_Pallet_Size", header: [{ text: "Length", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
                                            { id: "Height_Pallet_Size", header: [{ text: "Height", css: { "text-align": "center" } }, { content: "textFilter" }], width: 100, css: { "text-align": "center" }, },
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