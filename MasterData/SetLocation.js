var header_SetLocation = function () {
    var menuName = "SetLocation_", fd = "MasterData/" + menuName + "data.php";
    var map = null;

    function init() {
        //loadData(null);
        if (typeof longdo == 'undefined') {
            const script = document.createElement('script');
            // localhost
            // script.src = 'https://api.longdo.com/map/?key=ea26e128c1a4a4ed4c22c01c48340ceb';
            //cloud
            script.src = 'https://api.longdo.com/map/?key=ee63d796622e19a03f3276d2284ae3c2';

            document.body.appendChild(script);
            script.onload = () => {
                initMap();
            };
        }
        else {
            initMap();
        }
    };

    function initMap() {


        map = new longdo.Map(
            {
                placeholder: document.getElementById($n('map'))
            });

        map.Layers.externalOptions({ googleQuery: 'key=AIzaSyC9ItnaPb2x897MTFxXygqJdT6QPVTW6Hc' });
        map.Layers.setBase(longdo.Layers.GOOGLE_HYBRID);
        loadDataGeo(null);
        menuMap();


        map.Event.bind('toolbarChange', () => {
            if (map.Ui.Toolbar.measureList().length > 0) {
                var polygonObj = map.Ui.Toolbar.measureList()[0].location();
                var polygonAr = [];
                map.Overlays.remove(map.Ui.Toolbar.measureList()[0]);
                map.Ui.Toolbar.measureList().pop();
                for (var i = 0, len = polygonObj.length; i < len; i++) {
                    polygonAr.push(`${polygonObj[i].lat} ${polygonObj[i].lon}`);

                }
                ele('updateMap').show();
                ele('updateMap_polygon').setValue('POLYGON((' + polygonAr.join(',') + '))');
            }
        }
        );

        map.Search.placeholder(
            document.getElementById($n('searchBox'))
        );

        map.Search.placeholder(
            document.getElementById($n('result'))
        );

        var search = document.getElementById($n('searchBox'));
        var suggest = document.getElementById($n('suggest'));

        map.Event.bind('suggest', function (result) {
            if (result.meta.keyword != search.value) {
                if (result.meta.keyword != search.value) {
                    var searchResult = document.getElementById($n('result'));
                    while (searchResult.firstChild) {
                        searchResult.removeChild(searchResult.firstChild);
                    }
                    map.Overlays.clear();
                    return;
                }
                map.Overlays.clear();
                return;
            }
            else {
                suggest.innerHTML = '';
                for (var i = 0, item; item = result.data[i]; ++i) {
                    longdo.Util.append(suggest, 'a', {
                        innerHTML: item.d,
                        href: 'javascript:doSuggest(\'' + item.w + '\')'
                    });
                }
                suggest.style.display = 'block';
            }
        });

        search.addEventListener('input', function (event) {
            var query = search.value;
            map.Search.search(query);
        });

        function doSuggest(value) {
            if (search.value == '') {
                var searchResult = document.getElementById($n('result'));
                while (searchResult.firstChild) {
                    searchResult.removeChild(searchResult.firstChild);
                }
                map.Overlays.clear();
            }
            else {
                search.value = value;
                doSearch();
            }
        }

        function doSearch() {
            console.log(search.value);
            if (search.value == '') {
                var searchResult = document.getElementById($n('result'));
                while (searchResult.firstChild) {
                    searchResult.removeChild(searchResult.firstChild);
                }
                map.Overlays.clear();
            }
            else {
                map.Search.search(search.value);
                suggest.style.display = 'none';
            }
        }

        var searchclear = document.getElementById($n('searchclear'));

        searchclear.addEventListener('click', function (event) {
            clearSearch();
        });

        function clearSearch() {
            var searchBox = document.getElementById($n('searchBox'));
            var searchResult = document.getElementById($n('result'));
            searchBox.value = "";
            while (searchResult.firstChild) {
                searchResult.removeChild(searchResult.firstChild);
            }
            map.Overlays.clear();
            loadDataGeo();
        }
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

    function reload_options_supplier() {
        var supplierList = ele("Supplier").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=6");
    };

    function loadDataGeo(btn) {
        ajax(fd, {}, 2, function (json) {
            setTable('supplierView', json.data);
            map.Overlays.clear();
            for (var i = 0, len = json.data.length; i < len; i++) {

                var geoSupplier = JSON.parse(json.data[i].supplier_geo);

                if (geoSupplier.hasOwnProperty('type')) {
                    if (geoSupplier.type == 'Polygon') {
                        var arPoly = [];
                        for (var i2 = 0, len2 = geoSupplier.coordinates[0].length; i2 < len2; i2++) {
                            arPoly.push({ lon: geoSupplier.coordinates[0][i2][1], lat: geoSupplier.coordinates[0][i2][0] });
                        }
                        var polygon = new longdo.Polygon(arPoly, {
                            title: json.data[i].Supplier_Name_Short,
                            label: json.data[i].Supplier_Name_Short,
                            detail: json.data[i].Supplier_Name,
                            lineWidth: 2,
                            lineColor: 'rgba(0, 0, 0, 1)',
                            fillColor: 'rgba(255, 0, 0, 0.2)',
                            editable: false,
                            weight: longdo.OverlayWeight.Top
                        });
                        map.Overlays.add(polygon);
                    }
                }

                var polyCenterSupplier = JSON.parse(json.data[i].supplier_geoCenter), Supplier = '';
                if (polyCenterSupplier.hasOwnProperty('type')) {
                    if (polyCenterSupplier.type == 'Point') {
                        Supplier = json.data[i].supplier_code;

                        var wardLabel = new longdo.Marker(
                            { lon: polyCenterSupplier.coordinates[1], lat: polyCenterSupplier.coordinates[0] }, {
                            icon: {
                                url: 'images/factory.png',
                                size: { width: 30, height: 45 },
                                offset: { x: 12, y: 45 }
                            },
                            weight: longdo.OverlayWeight.Top,
                            visibleRange: { min: 9, max: 20 },
                        }
                        )
                        map.Overlays.add(wardLabel);
                    }
                }
            }

        }, btn);
    };

    webix.ui(
        {
            view: "window", move: true, modal: true, id: $n("updateMap"),
            close: true, move: true,
            head: {
                view: "toolbar",
                cols:
                    [
                        { view: "label", label: "เพิ่มพิกัด", align: "center" },
                    ],
            }, top: 50, position: "center",
            body:
            {
                view: "form", scroll: false, id: $n("updateMap_form1"), width: 400,
                elements:
                    [
                        {

                            cols:
                                [
                                    { view: "text", labelPosition: "top", required: true, labelWidth: 120, disabled: false, id: $n("updateMap_polygon"), name: "polygon", label: "Polygon", value: "" },
                                ]
                        },
                        {
                            cols:
                                [
                                    vw1('text', 'Supplier', 'Supplier', {
                                        suggest: "common/supplierMaster.php?type=5",
                                        on: {
                                            onBlur: function () {
                                                this.getList().hide();
                                            },
                                            onItemClick: function () {
                                                reload_options_supplier();
                                            }
                                        },
                                    }),
                                ]
                        },
                        {
                            cols:
                                [
                                    {},
                                    {
                                        view: "button", label: "Save",
                                        width: 120, css: "webix_green",
                                        icon: "mdi mdi-content-save", type: "icon",
                                        tooltip: { template: "บันทึก", dx: 10, dy: 15 },
                                        click: function () {
                                            if (ele('updateMap_form1').validate()) {
                                                webix.confirm(
                                                    {
                                                        title: "กรุณายืนยัน", ok: "ใช่", cancel: "ไม่", text: "คุณต้องการบันทึกข้อมูล<br><font color='#27ae60'><b>ใช่</b></font> หรือ <font color='#3498db'><b>ไม่</b></font>",
                                                        callback: function (res) {
                                                            if (res) {

                                                                $.post(fd, { obj: ele('updateMap_form1').getValues(), type: 21 })
                                                                    .done(function (ddd) {
                                                                        var data = JSON.parse(ddd);
                                                                        if (data.ch == 1) {
                                                                            loadDataGeo(null);
                                                                            ele("updateMap_Clear").callEvent("onItemClick", []);
                                                                        }
                                                                        else if (data.ch == 2) { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: data.data, callback: function () { } }); }
                                                                        else if (data.ch == 9) { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: data.data, callback: function () { } }); }
                                                                        else if (data.ch == 10) { webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: data.data, callback: function () { window.open("login.php", "_self"); } }); }
                                                                    });
                                                            }
                                                        }
                                                    });
                                            }
                                            else {
                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'กรุณาป้อนข้อมูล<font color="#ce5545"><b>ในช่องสีแดง</b></font>ให้ครบ', callback: function () { } });
                                            }
                                        }
                                    },
                                    {
                                        view: "button", label: "Cancel", id: $n("updateMap_Clear"),
                                        width: 120, css: "webix_red",
                                        icon: "mdi mdi-cancel", type: "icon",
                                        tooltip: { template: "ยกเลิก", dx: 10, dy: 15 },
                                        on:
                                        {
                                            onItemClick: function (id) {
                                                ele('updateMap').hide();
                                                ele('updateMap_form1').setValues('');
                                            }
                                        }
                                    }
                                ]
                        }
                    ]
            }
        });

    function menuMap() {
        $('.ldmap_topright').append(`
                <div id="${$n('listA')}"></div>
            `);

        webix.ui({
            container: $n('listA'),
            height: 0,
            width: 450,
            rows:
                [
                    {
                        view: "segmented", id: 'tabbar', value: $n('Hide'), multiview: true, optionWidth: 100, align: "center", padding: 0, options: [
                            { value: 'Supplier', id: $n('supplierView') },
                            { value: 'Hide', id: $n('Hide') }
                        ], on:
                        {
                            onChange: function (newv, oldv) {
                                if (newv != $n('Hide')) {
                                    ele('viewdata').show();
                                    /* this.config.height = 100;
                                    this.refresh(); */
                                }
                                else if (newv == $n('Hide')) {
                                    ele('viewdata').hide();
                                    map.Overlays.clear();
                                    loadDataGeo();
                                }
                                else if (newv == $n('supplierView')) {
                                    ele('truckView').hide();
                                    //ele('truckHistory').hide();
                                    ele('supplierView').show();
                                }
                            }
                        }
                    },
                    {
                        id: $n('viewdata'),
                        hidden: true,
                        rows:
                            [
                                {
                                    view: "datatable", id: $n("supplierView"), navigation: true,
                                    resizeColumn: true, select: 'row', height: 500,
                                    threeState: true,
                                    datatype: "json",
                                    columns:
                                        [
                                            //{ id: "Supplier_Code", header: ["Supplier Code", { content: "textFilter" }], width: 100 },
                                            { id: "Supplier_Name_Short", header: ["Supplier", { content: "textFilter" }], width: 100 },
                                            { id: "Supplier_Name", header: ["Supplier Name", { content: "textFilter" }], width: 250 },
                                            //{ id: "Customer_Code", header: ["Customer", { content: "textFilter" }], width: 100 },
                                        ], on:
                                    {
                                        onSelectChange: function () {
                                            var obj = this.getItem(this.getSelectedId(false));
                                            if (obj) {
                                                var supplier_geoCenter = JSON.parse(obj.supplier_geoCenter);
                                                if (_.has(supplier_geoCenter, 'type')) {
                                                    if (supplier_geoCenter.type == 'Point') {
                                                        map.location({ lat: supplier_geoCenter.coordinates[0], lon: supplier_geoCenter.coordinates[1] });

                                                    }
                                                }
                                            }

                                        }
                                    }
                                },
                            ]
                    }

                ]

        });
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_SetLocation",
        body:
        {
            id: "SetLocation_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Set Location", type: "header" },
                    {
                        view: "htmlform",
                        id: "formView",
                        template: `
                        <head>
                            <meta charset="utf-8">
                            <title>Longdo Map with MySQL</title>
                        <head>
                            <style>
                                html {
                                    height: 100%;
                                }

                                body {
                                    margin: 0px;
                                    height: 100%;
                                }

                                #map {
                                    height: 100%;
                                    width: 100%;
                                    position: absolute;
                                }

                                .suggest {
                                    display: none;
                                    position: absolute;
                                    z-index: 100;
                                    background-color: #fff;
                                    border: 1px solid #ccc;
                                    box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.3);
                                    overflow-y: auto;
                                    max-height: 200px;
                                    width: 100%;
                                }

                                .suggest-item {
                                    padding: 5px;
                                    cursor: pointer;
                                }

                                .suggest-item:hover {
                                    background-color: #f5f5f5;
                                }

                                .box {
                                    background-color: #ffffff;
                                    position: absolute;
                                    border-radius: 6px;
                                    top: 100px;
                                    left: 100px;
                                    padding: 10px;
                                    width: auto;
                                }

                                form {
                                    position: relative;
                                    width: 200px;
                                }

                                form input {
                                    width: 100%;
                                    padding-right: 20px;
                                    box-sizing: border-box;
                                }

                                form input:placeholder-shown+button {
                                    opacity: 0;
                                    pointer-events: none;
                                }

                                form button {
                                    position: absolute;
                                    border: none;
                                    display: block;
                                    width: 15px;
                                    height: 15px;
                                    line-height: 16px;
                                    font-size: 12px;
                                    border-radius: 50%;
                                    top: 0;
                                    bottom: 0;
                                    right: 5px;
                                    margin: auto;
                                    background: #ddd;
                                    padding: 0;
                                    outline: none;
                                    cursor: pointer;
                                    transition: .1s;
                                }
                            </style>
                        </head>
                        <body>
                            <div id='${$n('map')}' class='map' style="height: 100%;">
                            </div>
                            <div class="box">
                                <div class="input-group">
                                    <div class="form-inline">
                                        <input class="form-control form-control-lg" type="text" placeholder="Search for a location..."
                                            id='${$n('searchBox')}'>
                                            <button id='${$n('searchclear')}' type="reset" class="button">&times;</button>
                                            
                                    </div>
                                </div>

                                <form id='${$n('result')}'></form>
                                <div class='${$n('suggest')}'></div>
                            </div>
                        </body>`,
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