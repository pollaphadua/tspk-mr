var header_Dashboard = function () {
    var menuName = "Dashboard_", fd = "Report/" + menuName + "data.php";

    function init() {
        //refreshAt(0, 0, 0); //Will refresh the page at 00:00
        loadTruckData();
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

    function setList(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
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
    };

    function loadTruckData(btn) {
        var obj = ele("form1").getValues();
        ajax(fd, obj, 1, function (json) {
            setList("list1", json.data);
            var data = json.data;

            var dataArray = [];
            for (var i = 0; i < data.length; i++) {
                var Truck = data[i]['Truck_Number'];
                dataArray.push(Truck);
            }

            getRouteDayData(dataArray);
            getRouteNightData(dataArray);

            for (var i = 0; i < data.length; i++) {
                var TruckShow = data[i];
                setList("listTruck" + i, TruckShow);
            }

            for (var i = 0; i < 20; i++) {
                if (i >= data.length) {
                    ele('listTruck' + i).hide();
                    ele('listDay' + i).hide();
                    ele('listNight' + i).hide();
                }
                else {
                    ele('listTruck' + i).show();
                    ele('listDay' + i).show();
                    ele('listNight' + i).show();
                }
            }
            setInterval(function () {
                init();
            }, 1800000);  // 30 min

        }, btn);
    };


    function getRouteDayData(Truck) {
        var obj1 = ele("form1").getValues();
        ajax(fd, obj1, 2, function (json) {
            var data = json.data;
            var dataArray = [];

            for (i = 0; i < Truck.length; i++) {
                for (j = 0; j < data.length; j++) {
                    if (Truck[i] == data[j].Truck_Number) {
                        // console.log(Truck[i], ' = ', data[j].Truck_Number);
                        // console.log(data[j]);
                        dataArray.push(data[j]);
                        //data.splice(j, 1);
                    }
                }
                setList("listDay" + i, dataArray);
                dataArray = [];
            }
        });
    };


    function getRouteNightData(Truck) {
        var obj1 = ele("form1").getValues();
        ajax(fd, obj1, 3, function (json) {
            var data = json.data;
            var dataArray = [];

            for (i = 0; i < Truck.length; i++) {
                for (j = 0; j < data.length; j++) {
                    if (Truck[i] == data[j].Truck_Number) {
                        dataArray.push(data[j]);
                        //data.splice(j, 1);
                    }
                }
                setList("listNight" + i, dataArray);
                dataArray = [];

            }
        });
    };

    var listTruckLoop = [];

    for (var i = 0; i < 20; i++) {
        var listTruck = {
            view: "list",
            id: $n('listTruck' + i),
            height: 500,
            width: 80,
            scroll: "x",
            layout: "x",
            select: false,
            type: {
                width: 80,
                height: 140,
                scroll: true,
            },
            template: function (obj) {
                return `<div style="font-weight:bold;">${obj.row_num}. ${obj.Truck_Number}</div>`
            },
        };
        listTruckLoop.push(listTruck);
    };


    var listRouteDayLoop = [];

    for (var i = 0; i < 20; i++) {
        var listRouteDay = {
            view: "list",
            id: $n('listDay' + i),
            height: 500,
            scroll: "x",
            layout: "x",
            select: false,
            type: {
                width: 120,
                height: 140,
                scroll: true,
            },
            scheme: {
                // "font-weight": "bold"
                $init: function (obj) {
                    if (obj.Time_Status == 'Early') obj.$css = { "background": "#9BC2E6", };
                    if (obj.Time_Status == 'Delay') obj.$css = { "background": "#FF6969", };
                    if (obj.Time_Status == 'On Time') obj.$css = { "background": "#A9D08E", };
                    if (obj.Time_Status == 'Waiting') obj.$css = { "background": "#FFD966", };
                    if (obj.Time_Status == 'Overdue') obj.$css = { "background": "#dadee0", };
                }
            },
            template: function (obj) {
                return `
                <div style="font-weight:bold; text-align:center;">${obj.Status_Pickup}</div>
                <div>Route : ${obj.Route_Code}</div>
                <div>Supplier : ${obj.Supplier_Name_Short}</div>
                <div>Plan in : ${obj.planin_time}</div>
                <div>Actual in : ${obj.actual_in_time}</div>
                <div style="background-color:#ffffff; text-align:center;">${obj.Time_Status}</div>
                `
            }
        };
        listRouteDayLoop.push(listRouteDay);
    };


    var listRouteNightLoop = [];

    for (var i = 0; i < 20; i++) {
        var listRouteNight = {
            view: "list",
            id: $n('listNight' + i),
            height: 500,
            scroll: "x",
            layout: "x",
            select: false,
            type: {
                width: 120,
                height: 140,
                scroll: true,
            },
            scheme: {
                // "font-weight": "bold"
                $init: function (obj) {
                    if (obj.Time_Status == 'Early') obj.$css = { "background": "#9BC2E6", };
                    if (obj.Time_Status == 'Delay') obj.$css = { "background": "#FF6969", };
                    if (obj.Time_Status == 'On Time') obj.$css = { "background": "#A9D08E", };
                    if (obj.Time_Status == 'Waiting') obj.$css = { "background": "#FFD966", };
                    if (obj.Time_Status == 'Overdue') obj.$css = { "background": "#dadee0", };
                }
            },
            template: function (obj) {
                return `
                <div style="font-weight:bold; text-align:center;">${obj.Status_Pickup}</div>
                <div>Route : ${obj.Route_Code}</div>
                <div>Supplier : ${obj.Supplier_Name_Short}</div>
                <div>Plan in : ${obj.planin_time}</div>
                <div>Actual in : ${obj.actual_in_time}</div>
                <div style="background-color:#ffffff; text-align:center;">${obj.Time_Status}</div>
                `
            }
        };
        listRouteNightLoop.push(listRouteNight);
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
        id: "header_Dashboard",
        body:
        {
            id: "Dashboard_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Dashboard Truck Monitor", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        paddingY: 5,
                        elements:
                            [
                                {
                                    cols: [
                                        vw1('combo', 'Customer_Code', 'Site', {
                                            required: false, height: 55, width: 150,
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
                                        vw1("datepicker", 'Operation_Date', "Operation Date", {
                                            value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat,
                                            height: 55, width: 200,
                                        }),
                                        {
                                            rows: [
                                                {},
                                                vw1('button', 'find1', 'Find', {
                                                    width: 120, css: "webix_primary",
                                                    icon: "mdi mdi-magnify", type: "icon",
                                                    tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                    height: 30,
                                                    on:
                                                    {
                                                        onItemClick: function () {
                                                            loadTruckData();
                                                        }
                                                    },
                                                }),
                                            ]
                                        },
                                        {
                                            rows: [
                                                {},
                                                vw1("button", 'btnExport1', "Export Data", {
                                                    width: 120, css: "webix_orange",
                                                    icon: "mdi mdi-table-arrow-down", type: "icon",
                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                    height: 30,
                                                    on: {
                                                        onItemClick: function () {
                                                            var obj = ele("form1").getValues();
                                                            var Operation_Date = obj.Operation_Date;
                                                            var Customer_Code = obj.Customer_Code;
                                                            var temp = window.open(fd + "?type=5" + "&Operation_Date=" + Operation_Date + "&Customer_Code=" + Customer_Code);
                                                            temp.addEventListener('load', function () { temp.close(); }, false);
                                                        }
                                                    }
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
                                                    height: 30,
                                                    on:
                                                    {
                                                        onItemClick: function () {
                                                            ele("Operation_Date").setValue(new Date());
                                                            ele('Customer_Code').setValue('');
                                                            reload_options_customer();
                                                            loadTruckData();
                                                        }
                                                    }
                                                }),
                                            ]
                                        },
                                        {}
                                    ]
                                },
                            ]
                    },
                    {
                        cols: [
                            { template: "Truck", type: "header", width: 80, height: 35, css: { "text-align": "center" } },
                            { template: "<b>Day</b>", height: 35, type: "section", css: { "text-align": "center" } },
                            { template: "<b>Night</b>", height: 35, type: "section", css: { "text-align": "center" } },
                        ]
                    },
                    {
                        cols: [
                            { rows: listTruckLoop },
                            { rows: listRouteDayLoop },
                            { rows: listRouteNightLoop },
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