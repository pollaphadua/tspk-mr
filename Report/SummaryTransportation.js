var header_SummaryTransportation = function () {
    var menuName = "SummaryTransportation_", fd = "Report/" + menuName + "data.php";

    function init() {
        reload_options_customer();
        reload_options_period();

        loadSummaryCbmDaily();
        loadSummaryCbmMonthly();
        loadSummaryTrip();


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


    function setChart(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
        //ele(tableName).filterByAll();
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

    //Daily
    function loadSummaryCbmDaily(btn) {
        webix.extend(ele("chart_sumarycbm_daily"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarycbm_daily").clearAll();
        ele("chart_sumarycbm_daily").disable();
        ele("chart_sumarycbm_daily").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form1").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 1, function (json) {
                var data = json.data;
                if (data.length > 0) {
                    setChart("chart_sumarycbm_daily", data);
                    ele("Total_CBM").setValue(data[0].Total_CBM);
                }
                else {
                    ele("chart_sumarycbm_daily").clearAll();
                    ele("Total_CBM").setValue('');
                }
            }, btn);
            ele("chart_sumarycbm_daily").enable();
        }, 500);
    };


    //Monthly
    function loadSummaryCbmMonthly(btn) {
        webix.extend(ele("chart_sumarycbm_monthly"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarycbm_monthly").clearAll();
        ele("chart_sumarycbm_monthly").disable();
        ele("chart_sumarycbm_monthly").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form1").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 2, function (json) {
                var data = json.data;
                if (data.length > 0) {
                    setChart("chart_sumarycbm_monthly", data);
                    ele("Total_CBM_Month").setValue(data[0].Total_CBM);
                }
                else {
                    ele("chart_sumarycbm_monthly").clearAll();
                    ele("Total_CBM_Month").setValue('');
                }
            }, btn);
            ele("chart_sumarycbm_monthly").enable();
        }, 500);
    };


    //Trip
    function loadSummaryTrip(btn) {
        webix.extend(ele("chart_sumarytrip"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarytrip").clearAll();
        ele("chart_sumarytrip").disable();
        ele("chart_sumarytrip").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form2").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 3, function (json) {
                var data = json.data;
                if (data.length > 0) {
                    setChart("chart_sumarytrip", data);
                    ele("Total_Trip").setValue(data[0].Total_Trip);
                }
                else {
                    ele("chart_sumarytrip").clearAll();
                    ele("Total_Trip").setValue('');
                }
            }, btn);
            ele("chart_sumarytrip").enable();
        }, 500);
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
        var Customer_Code = ele('Customer_Code').getValue();
        if (Customer_Code != '') {
            ele('export_billing_excel').enable();
            ele('btnExport1').enable();
            ele('btnExport3').enable();
        } else {
            ele('export_billing_excel').disable();
            ele('btnExport1').disable();
            ele('btnExport3').disable();
        }
    };

    function reload_options_period() {
        $.post('common/dateCommon.php', { type: 1 })
            .done(function (data) {
                var json = JSON.parse(data);
                data = eval('(' + data + ')');
                if (json.ch == 1) {
                    var data1 = json.data;
                    if (data1.length <= 1) {
                        //console.log(data1);
                        ele('Period').setValue(data1[0].Period_Show);
                        ele('Period_Trip').setValue(data1[0].Period_Show);
                    }
                }
            });
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_SummaryTransportation",
        body:
        {
            id: "SummaryTransportation_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Summary Transportation", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form_customer'),
                        elements:
                            [
                                {
                                    cols: [
                                        vw1('combo', 'Customer_Code', 'Customer', {
                                            required: false, width: 200,
                                            suggest: "common/customerMaster.php?type=1",
                                            on: {
                                                onBlur: function () {
                                                    this.getList().hide();
                                                },
                                                onItemClick: function () {
                                                    reload_options_customer();
                                                },
                                                onChange: function () {
                                                    reload_options_customer();
                                                    reload_options_period();
                                                    loadSummaryCbmDaily();
                                                    loadSummaryCbmMonthly();
                                                    loadSummaryTrip();
                                                },
                                            },
                                        }),
                                        {
                                            rows: [
                                                {},
                                                vw1("button", 'clear', "Clear", {
                                                    width: 120, css: "webix_secondary",
                                                    icon: "mdi mdi-backspace", type: "icon",
                                                    tooltip: { template: "ล้างข้อมูล", dx: 10, dy: 15 },
                                                    on:
                                                    {
                                                        onItemClick: function () {
                                                            ele('Customer_Code').setValue('');
                                                            reload_options_customer();
                                                            reload_options_period();
                                                            loadSummaryCbmDaily();
                                                            loadSummaryCbmMonthly();
                                                            loadSummaryTrip();
                                                        }
                                                    }
                                                }),
                                            ]
                                        },
                                        {}
                                    ]
                                }
                            ]
                    },
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        //hidden:1,
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Summary CBM",
                                    body: {
                                        rows: [
                                            { height: 15 },
                                            {
                                                view: "fieldset",
                                                label: "Daily results",
                                                hidden: 0,
                                                body: {
                                                    rows: [
                                                        {
                                                            cols:
                                                                [
                                                                    //vw1("datepicker", 'Start_Date', "Date (วันที่)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 200, required: false }),
                                                                    vw1("datepicker", 'Start_Date', "Start Date (วันที่เริ่ม)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 200 }),
                                                                    vw1("datepicker", 'Stop_Date', "End Date (วันที่สิ้นสุด)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 200 }),
                                                                    {
                                                                        rows: [
                                                                            {},
                                                                            vw1('button', 'find1', 'Find', {
                                                                                width: 120, css: "webix_primary",
                                                                                icon: "mdi mdi-magnify", type: "icon",
                                                                                tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                on:
                                                                                {
                                                                                    onItemClick: function () {
                                                                                        loadSummaryCbmDaily();
                                                                                    }
                                                                                },
                                                                            }),
                                                                        ]
                                                                    },
                                                                    {}
                                                                ]
                                                        },
                                                        {
                                                            paddingY: 5,
                                                            cols: [
                                                                {},
                                                                vw1("button", 'btnExport1', "Export Report", {
                                                                    width: 120, css: "webix_orange",
                                                                    icon: "mdi mdi-table-arrow-down", type: "icon",
                                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                                    on: {
                                                                        onItemClick: function () {
                                                                            var dataT1 = ele("chart_sumarycbm_daily");
                                                                            if (dataT1.count() != 0) {
                                                                                var Start_Date = ele('Start_Date').getValue();
                                                                                var Stop_Date = ele('Stop_Date').getValue();
                                                                                var Customer_Code = ele('Customer_Code').getValue();
                                                                                var temp = window.open(fd + "?type=51" + "&Start_Date=" + Start_Date + "&Stop_Date=" + Stop_Date + "&Customer_Code=" + Customer_Code);
                                                                                temp.addEventListener('load', function () {
                                                                                    //temp.close();
                                                                                }, false);
                                                                            }
                                                                            else {
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                            }
                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        },
                                                        {
                                                            height: 25,
                                                            cols: [
                                                                {
                                                                    view: "label",
                                                                    label: "Summary Transportation Total : ",
                                                                    type: "clean",
                                                                    css: { "text-align": "right", },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    id: $n('Total_CBM'),
                                                                    width: 100,
                                                                    type: "clean",
                                                                    css: { "text-align": "center", "background-color": "#def0fc" },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    label: " CBM.",
                                                                    type: "clean",
                                                                    css: { "text-align": "left", },
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            body:
                                                            {
                                                                view: "chart",
                                                                id: $n('chart_sumarycbm_daily'),
                                                                width: 600,
                                                                height: 250,
                                                                type: "line",
                                                                value: "#CBM#",
                                                                item: {
                                                                    borderColor: "#36abee",
                                                                    color: "#ffffff"
                                                                },
                                                                line: {
                                                                    color: "#36abee",
                                                                    width: 3
                                                                },
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.Supplier}</div>`; }
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    end: 300,
                                                                    step: 50,
                                                                    template: function (obj) {
                                                                        return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                    }
                                                                },
                                                                label: function (obj) {
                                                                    return `<div style="font-size:8px; text-align:center; background-color:#ffffff">${obj.CBM}</div>`;
                                                                },
                                                            },
                                                        },
                                                    ],
                                                },
                                            },
                                            { height: 20 },


                                            // Monthly CBM
                                            {
                                                view: "fieldset",
                                                label: "CBM Monthly results",
                                                body: {
                                                    rows: [
                                                        {
                                                            cols:
                                                                [
                                                                    // vw1("datepicker", 'Start_Date', "Start Date (วันที่เริ่ม)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 200 }),
                                                                    // vw1("datepicker", 'Stop_Date', "End Date (วันที่สิ้นสุด)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, width: 200 }),
                                                                    //vw1("datepicker", 'Operation_Month', "Month (เดือน)", { type: "month", format: "%M-%Y", required: false, width: 200, }),
                                                                    vw1("combo", 'Period', "Period", { required: false, width: 200, suggest: fd + "?type=9", yCount: "21", }),
                                                                    {
                                                                        rows: [
                                                                            {},
                                                                            vw1('button', 'find2', 'Find', {
                                                                                width: 120, css: "webix_primary",
                                                                                icon: "mdi mdi-magnify", type: "icon",
                                                                                tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                on:
                                                                                {
                                                                                    onItemClick: function () {
                                                                                        loadSummaryCbmMonthly();
                                                                                    }
                                                                                },
                                                                            }),
                                                                        ]
                                                                    },
                                                                    {}
                                                                ]
                                                        },
                                                        {
                                                            paddingY: 5,
                                                            cols: [
                                                                {},
                                                                {
                                                                    view: "button", id: $n('export_billing_excel'), label: "Export Report",
                                                                    width: 120, css: "webix_orange",
                                                                    icon: "mdi mdi-table-arrow-down", type: "icon",
                                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                                    on:
                                                                    {
                                                                        onItemClick: async (id, e) => {
                                                                            var dataT1 = ele("chart_sumarycbm_monthly");
                                                                            if (dataT1.count() != 0) {
                                                                                var obj1 = ele('form_customer').getValues();
                                                                                var obj2 = ele('form1').getValues();
                                                                                var obj = { ...obj1, ...obj2 };
                                                                                webix.extend($$("SummaryTransportation_id"), webix.ProgressBar);
                                                                                //ele("export_billing_excel").disable();
                                                                                $$("SummaryTransportation_id").showProgress({
                                                                                    type: "top",
                                                                                    delay: 100000,
                                                                                    hide: true
                                                                                });

                                                                                setTimeout(function () {
                                                                                    $.post(fd, { obj: obj, type: 52 })
                                                                                        .done(function (data) {
                                                                                            var json = JSON.parse(data);
                                                                                            data = eval('(' + data + ')');
                                                                                            if (json.ch == 1) {
                                                                                                webix.alert({
                                                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-complete", ok: 'ตกลง', text: 'Export Complete', callback: function () {
                                                                                                        ele("export_billing_excel").enable();
                                                                                                        $$("SummaryTransportation_id").hideProgress();
                                                                                                        window.location.href = 'Report/' + json.data;
                                                                                                    }
                                                                                                });
                                                                                            }
                                                                                            else if (json.ch == 2) {
                                                                                                webix.alert({
                                                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-error", ok: 'ตกลง', text: json.data, callback: function () {
                                                                                                        ele("export_billing_excel").enable();
                                                                                                        $$("SummaryTransportation_id").hideProgress();
                                                                                                        window.playsound(2);
                                                                                                    }
                                                                                                });
                                                                                            }
                                                                                            else {
                                                                                                webix.alert({
                                                                                                    title: "<b>ข้อความจากระบบ</b>", type: "alert-warning", ok: 'ตกลง', text: 'ผิดพลาดโปรดลองอีกครั้ง', callback: function () {
                                                                                                        ele("export_billing_excel").enable();
                                                                                                        $$("SummaryTransportation_id").hideProgress();
                                                                                                        window.playsound(2);
                                                                                                    }
                                                                                                });
                                                                                            }
                                                                                        })
                                                                                }, 0);
                                                                            }
                                                                            else {
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                            }



                                                                        }
                                                                    }
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            height: 25,
                                                            cols: [
                                                                {
                                                                    view: "label",
                                                                    label: "Summary Transportation Total : ",
                                                                    type: "clean",
                                                                    css: { "text-align": "right", },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    id: $n('Total_CBM_Month'),
                                                                    width: 100,
                                                                    type: "clean",
                                                                    css: { "text-align": "center", "background-color": "#def0fc" },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    label: " CBM.",
                                                                    type: "clean",
                                                                    css: { "text-align": "left", },
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            body:
                                                            {
                                                                view: "chart",
                                                                id: $n('chart_sumarycbm_monthly'),
                                                                width: 600,
                                                                height: 300,
                                                                type: "line",
                                                                value: "#CBM#",
                                                                item: {
                                                                    borderColor: "#36abee",
                                                                    color: "#ffffff"
                                                                },
                                                                line: {
                                                                    color: "#36abee",
                                                                    width: 3
                                                                },
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.Supplier}</div>`; }
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    end: 4000,
                                                                    step: 500,
                                                                    template: function (obj) {
                                                                        return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                    }
                                                                },
                                                                label: function (obj) {
                                                                    return `<div style="font-size:8px; text-align:center; background-color:#ffffff">${obj.CBM}</div>`;
                                                                },
                                                            },
                                                        },
                                                    ],
                                                },
                                            },
                                        ]
                                    }
                                },
                            ]
                    },

                    // Summart Trip - Supplier
                    {
                        view: "form", scroll: false, id: $n('form2'),
                        hidden: 0,
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Summary Trip",
                                    body: {
                                        rows: [
                                            { height: 15 },
                                            {
                                                view: "fieldset",
                                                label: "Monthly results",
                                                body: {
                                                    rows: [
                                                        {
                                                            cols:
                                                                [
                                                                    vw2("combo", 'Period_Trip', 'Period', "Period", { required: false, width: 200, suggest: fd + "?type=9", yCount: "21", }),
                                                                    //vw2("datepicker", 'Period_Trip', 'Operation_Month', "Month (เดือน)", { type: "month", format: "%M-%Y", required: false, width: 200, }),
                                                                    {
                                                                        rows: [
                                                                            {},
                                                                            vw1('button', 'find3', 'Find', {
                                                                                width: 120, css: "webix_primary",
                                                                                icon: "mdi mdi-magnify", type: "icon",
                                                                                tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                                on:
                                                                                {
                                                                                    onItemClick: function () {
                                                                                        loadSummaryTrip();
                                                                                    }
                                                                                },
                                                                            }),
                                                                        ]
                                                                    },
                                                                    {}
                                                                ]
                                                        },
                                                        {
                                                            paddingY: 5,
                                                            cols: [
                                                                {},
                                                                vw1("button", 'btnExport3', "Export Report", {
                                                                    width: 120, css: "webix_orange",
                                                                    icon: "mdi mdi-table-arrow-down", type: "icon",
                                                                    tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                                    on: {
                                                                        onItemClick: function () {
                                                                            var dataT1 = ele("chart_sumarytrip");
                                                                            if (dataT1.count() != 0) {
                                                                                var Period = ele('Period').getValue();
                                                                                var Customer_Code = ele('Customer_Code').getValue();
                                                                                var temp = window.open(fd + "?type=53" + "&Period=" + Period + "&Customer_Code=" + Customer_Code);
                                                                                temp.addEventListener('load', function () { temp.close(); }, false);
                                                                            }
                                                                            else {
                                                                                webix.alert({ title: "<b>ข้อความจากระบบ</b>", ok: 'ตกลง', text: 'ไม่พบข้อมูลในตาราง', callback: function () { } });
                                                                            }
                                                                        }
                                                                    }
                                                                }),
                                                            ]
                                                        },
                                                        {
                                                            height: 25,
                                                            cols: [
                                                                {
                                                                    view: "label",
                                                                    label: "Summary Transportation Total : ",
                                                                    type: "clean",
                                                                    css: { "text-align": "right", },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    id: $n('Total_Trip'),
                                                                    width: 100,
                                                                    type: "clean",
                                                                    css: { "text-align": "center", "background-color": "#def0fc" },
                                                                },
                                                                {
                                                                    view: "label",
                                                                    label: " Trip",
                                                                    type: "clean",
                                                                    css: { "text-align": "left", },
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            body:
                                                            {
                                                                view: "chart",
                                                                id: $n('chart_sumarytrip'),
                                                                type: "bar",
                                                                //width: 1000,
                                                                height: 300,
                                                                value: "#trip#",
                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.trip}</div>`; },
                                                                color: "#36abee",
                                                                gradient: "rising",
                                                                radius: 2,
                                                                //barWidth:25,
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.Supplier}</div>`; },
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    step: 50,
                                                                    end: 200,
                                                                    template: function (obj) {
                                                                        return (`<div style="font-size:10px; text-align:center;">${obj}</div>`)
                                                                        //return (obj % 200 ? "" : `<div style="font-size:10px; text-align:center;">${obj}</div>`)
                                                                    }
                                                                },
                                                            }
                                                        },
                                                    ],
                                                },
                                            },
                                        ]
                                    }
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