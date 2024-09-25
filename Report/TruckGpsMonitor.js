var header_TruckGpsMonitor = function () {
    var menuName = "TruckGpsMonitor_", fd = "Report/" + menuName + "data.php";
    var line_truckToSup = null
    function init() {
        ShowMap();
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


    function ShowMap() {
        if (typeof longdo == 'undefined') {
            const script = document.createElement('script');
            // localhost
            script.src = 'https://api.longdo.com/map/?key=ea26e128c1a4a4ed4c22c01c48340ceb';
            //cloud
            // script.src = 'https://api.longdo.com/map/?key=ee63d796622e19a03f3276d2284ae3c2';
            document.body.appendChild(script);
            script.onload = () => {
                initMap();
            };
        }
        else {
            initMap();
        }
    }

    function initMap() {
        map = new longdo.Map(
            {
                placeholder: document.getElementById($n('map'))
            });

        map.Layers.externalOptions({ googleQuery: 'key=AIzaSyC9ItnaPb2x897MTFxXygqJdT6QPVTW6Hc' });
        map.Layers.setBase(longdo.Layers.GOOGLE_HYBRID);
        map.Ui.Toolbar.visible(false);
        map.Ui.Geolocation.visible(false);
        loadDataGeo(null);
        //loadDataGeoTruck(null);
        loadTruckGeoMaster(null);
        menuMap();

        setInterval(function () {
            map.Overlays.clear();
            loadDataGeo();
            //loadDataGeoTruck();
            loadTruckGeoMaster();
        }, 10000);

        // setInterval(function () {
        //     $.ajax({
        //         type: "POST",
        //         data: {},
        //         //url: "Report/TruckGpsMonitor.php",
        //         url:"https://wms.albatrossthai.com/tspk-mr/Report/TruckGpsMonitor.php",
        //         success: function (msg) {
        //         }
        //     });
        // }, 5000);

        /*
        10 วินาที = 10000
        1 นาที = 60000
        2 นาที = 120000
        3 นาที = 180000
        4 นาที = 240000
        5 นาที = 300000
        10 นาที = 600000
        */
    };

    function reload_options_truck() {
        var truckList = ele("Truck").getPopup().getList();
        truckList.clearAll();
        truckList.load(fd + "?type=6")
    };

    function reload_options_supplier() {
        var supplierList = ele("Supplier").getPopup().getList();
        supplierList.clearAll();
        supplierList.load("common/supplierMaster.php?type=4");
    };


    function loadDataGeo(btn) {
        ajax(fd, {}, 2, function (json) {
            setTable('supplierView', json.data);
            //map.Overlays.clear();
            for (var i = 0, len = json.data.length; i < len; i++) {

                var geoSupplier = JSON.parse(json.data[i].supplier_geo);

                if (geoSupplier.hasOwnProperty('type')) {
                    if (geoSupplier.type == 'Polygon') {
                        var arPoly = [];
                        for (var i2 = 0, len2 = geoSupplier.coordinates[0].length; i2 < len2; i2++) {
                            arPoly.push({ lon: geoSupplier.coordinates[0][i2][1], lat: geoSupplier.coordinates[0][i2][0] });
                        }
                        var polygon = new longdo.Polygon(arPoly, {
                            title: json.data[i].Supplier_Name_Short + "/Supplier",
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
                        Supplier = json.data[i].Supplier_Name_Short;

                        var wardLabel = new longdo.Marker(
                            { lon: polyCenterSupplier.coordinates[1], lat: polyCenterSupplier.coordinates[0] }, {
                            title: Supplier,
                            icon: {
                                url: 'images/factory.png',
                                size: { width: 30, height: 45 },
                                offset: { x: 12, y: 45 }
                            },
                            weight: longdo.OverlayWeight.Top,
                            //visibleRange: { min: 9, max: 20 },
                        }
                        )
                        map.Overlays.add(wardLabel);
                    }
                }
            }

        }, btn);
    };


    function loadTruckGeoMaster(btn) {
        ajax(fd, {}, 1, function (json) {
            setTable('truckView', json.data);
            //map.Overlays.clear();
            for (var i = 0, len = json.data.length; i < len; i++) {

                var pointTruck = JSON.parse(json.data[i].truck_geo), Truck = '';
                if (pointTruck.hasOwnProperty('type')) {
                    if (pointTruck.type == 'Point') {
                        Truck = json.data[i].Truck_Number;
                        gps_angle = json.data[i].gps_angle;

                        var markerTruck = new longdo.Marker(
                            { lon: pointTruck.coordinates[1], lat: pointTruck.coordinates[0] }, {
                            title: Truck + "/Truck",
                            rotate: gps_angle,
                            icon: {
                                url: 'images/car-top-view.png',
                                size: { width: 40, height: 45 },
                                offset: { x: 12, y: 45 }
                            },
                            draggable: false,
                            weight: longdo.OverlayWeight.Top,
                            //visibleRange: { min: 9, max: 20 },
                        });
                        map.Overlays.add(markerTruck);

                        var markerTruckTag = new longdo.Marker(
                            { lon: pointTruck.coordinates[1], lat: pointTruck.coordinates[0] }, {
                            title: Truck + "/Truck",
                            icon: {
                                html: `
                                <div class='webix_view' style='background-color:lightblue;opacity: 1;width:40px;' >
                                    <center><h10>${Truck}</h10></center>                                        
                                </div>
                                `,
                                offset: { x: 45, y: 27 }
                            },
                            draggable: false,
                            weight: longdo.OverlayWeight.Top,
                            //visibleRange:{ min: 15, max: 20 },
                        });
                        map.Overlays.add(markerTruckTag);
                    }
                }
            }
        }, btn);
    };



    function menuMap() {
        $('.ldmap_topright').append(`
            <div id="${$n('listA')}"></div>
        `);

        webix.ui({
            container: $n('listA'),
            height: 0,
            width: 400,
            rows:
                [
                    {
                        view: "segmented", id: 'tabbar', value: $n('Hide'), multiview: true, optionWidth: 100, align: "center", padding: 0, options: [
                            { value: 'Truck', id: $n('truckView') },
                            { value: 'Truck History', id: $n('truckHistory') },
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
                                }

                                if (newv == $n('truckView')) {
                                    ele('supplierView').hide();
                                    ele('truckHistory').hide();
                                    ele('truckView').show();
                                    loadTruckGeoMaster();
                                }
                                else if (newv == $n('supplierView')) {
                                    ele('truckView').hide();
                                    ele('truckHistory').hide();
                                    ele('supplierView').show();
                                }
                                else if (newv == $n('truckHistory')) {
                                    ele('truckView').hide();
                                    ele('supplierView').hide();
                                    ele('truckHistory').show();
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
                                    view: "datatable", id: $n("truckView"), navigation: true,
                                    resizeColumn: true, select: 'row',
                                    threeState: true, height: 650,
                                    datatype: "json",
                                    columns:
                                        [
                                            //{ id: "NO", header: "", css: "rank", width: 5, sort: "int" },
                                            { id: "Truck_Number", header: ["Truck No.", { content: "textFilter" }], width: 100 },
                                            { id: "gps_speed", header: ["Speed", { content: "textFilter" }], width: 100 },
                                            { id: "gps_updateDatetime", header: ["GPS Update", { content: "textFilter" }], width: 120 },
                                        ], on:
                                    {
                                        onSelectChange: function () {
                                            var obj = this.getItem(this.getSelectedId(false));
                                            if (obj) {
                                                // ajax(fd, {}, 1, function (json) {
                                                //     setTable('truckView', json.data);
                                                // });
                                                var geoTruck = JSON.parse(obj.truck_geo);
                                                if (_.has(geoTruck, 'type')) {
                                                    if (geoTruck.type == 'Point') {
                                                        map.location({ lat: geoTruck.coordinates[0], lon: geoTruck.coordinates[1] });
                                                    }
                                                }
                                            }
                                        }
                                    }
                                },
                                {
                                    view: "datatable", id: $n("supplierView"), navigation: true,
                                    resizeColumn: true, select: 'row', height: 650, rowHeight: 30,
                                    threeState: true,
                                    datatype: "json",
                                    columns:
                                        [
                                            //{ id: "NO", header: "", css: "rank", width: 5, sort: "int" },
                                            { id: "Supplier_Name_Short", header: ["Supplier Code", { content: "textFilter" }], width: 100 },
                                            { id: "Supplier_Name", header: ["Supplier Name", { content: "textFilter" }], width: 250 },
                                            //{ id: "Customer_Code", header: ["Customer", { content: "textFilter" }], width: 250 },
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
                                {
                                    view: "form", paddingY: 20,
                                    navigation: true, height: 650,
                                    id: $n("truckHistory"),
                                    elements:
                                        [
                                            {
                                                cols:
                                                    [
                                                        vw1('datepicker', 'dateStart', 'Start Date (วันเริ่มต้น)', { format: webix.Date.dateToStr("%Y-%m-%d"), value: new Date(), stringResult: 1, type: "date", }),
                                                        vw1('text', 'timeStart', 'Start Time (เวลาเริ่มต้น)', {}),
                                                    ]
                                            },
                                            {
                                                cols:
                                                    [
                                                        vw1('datepicker', 'dateEnd', 'End Date (วันสิ้นสุด)', { format: webix.Date.dateToStr("%Y-%m-%d"), value: new Date(), stringResult: 1, type: "date" }),
                                                        vw1('text', 'timeEnd', 'End Time (เวลาสิ้นสุด)', {}),
                                                    ]
                                            },
                                            {
                                                cols:
                                                    [
                                                        vw1('text', 'Truck', 'truck License (ทะเบียนรถ)', {
                                                            suggest: fd + "?type=5",
                                                        }),
                                                        vw1('text', 'Supplier', 'Supplier', {
                                                            suggest: "common/supplierMaster.php?type=5",
                                                        }),
                                                    ],
                                            },
                                            vw1('button', 'find1', 'Find (ค้นหา)', {
                                                on:
                                                {
                                                    onItemClick: function (id, e) {
                                                        var btn = this;
                                                        //console.log(ele('truckHistory').getValues());
                                                        ajax(fd, ele('truckHistory').getValues(), 4, function (json) {
                                                            setTable('historyView', json.data);
                                                        }, btn);
                                                    }
                                                }
                                            }),
                                            {
                                                view: "datatable", id: $n("historyView"), navigation: true,
                                                resizeColumn: true, select: 'row',
                                                threeState: true,
                                                datatype: "json",
                                                columns:
                                                    [
                                                        { id: "gps_updateDatetime", header: ["gps updateDatetime", { content: "textFilter" }], width: 200 },
                                                        { id: "Contain", header: ["อยู่ในกรอบหรือไม่", { content: "textFilter" }], width: 100 },
                                                    ], on:
                                                {
                                                    onSelectChange: function () {
                                                        var obj = this.getItem(this.getSelectedId(false));
                                                        if (obj) {
                                                            var geoTruck = JSON.parse(obj.pt);
                                                            var sup_geo = JSON.parse(obj.sup_geo);
                                                            if (_.has(geoTruck, 'type')) {
                                                                if (geoTruck.type == 'Point') {
                                                                    map.location({ lat: geoTruck.coordinates[0], lon: geoTruck.coordinates[1] });
                                                                }
                                                            }

                                                            if (_.has(sup_geo, 'type')) {
                                                                if (sup_geo.type == 'Point') {
                                                                    if (line_truckToSup) {
                                                                        map.Overlays.remove(line_truckToSup);
                                                                    }
                                                                    var lineOption =
                                                                    {
                                                                        lineWidth: 2,
                                                                        lineColor: 'rgba(240, 227, 94, 100)',
                                                                        linePattern: pattern2
                                                                    }
                                                                    line_truckToSup = new longdo.Polyline([{ lon: geoTruck.coordinates[1], lat: geoTruck.coordinates[0] },
                                                                    { lon: sup_geo.coordinates[1], lat: sup_geo.coordinates[0] }], lineOption);
                                                                    map.Overlays.add(line_truckToSup);


                                                                    function pattern2(context, i, x1, y1, x2, y2) {
                                                                        var size = 30;
                                                                        var angle = Math.PI / 6;
                                                                        var direction = Math.atan2(y2 - y1, x2 - x1);
                                                                        context.moveTo(x1, y1);
                                                                        context.lineTo(x2, y2);
                                                                        context.lineTo(x2 - size * Math.cos(direction - angle), y2 - size * Math.sin(direction - angle));
                                                                        context.moveTo(x2, y2);
                                                                        context.lineTo(x2 - size * Math.cos(direction + angle), y2 - size * Math.sin(direction + angle));
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            },

                                        ]
                                },
                            ]
                    }

                ]

        });
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_TruckGpsMonitor",
        body:
        {
            id: "TruckGpsMonitor_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "TRUCK GPS MONITOR", type: "header" },
                    {
                        view: "htmlform",
                        id: $n("formView"),
                        hidden: 0,
                        template: `
                        <div id='${$n('map')}' class='map' style="height: 100%;">
                        </div>`,
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