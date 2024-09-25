var header_SummaryTrip = function () {
    var menuName = "SummaryTrip_", fd = "Report/" + menuName + "data.php";

    function init() {
        //refreshAt(00, 00, 0); //Will refresh the page at 00:00
        reload_options_customer();
        loadSummaryTripDay();
        loadSummaryDelDay();
        loadSummaryTripDate();
        loadSummaryDelDate();
        loadSummaryTripMonth();
        loadSummaryDelMonth();
        loadSummaryTripYear();
        loadSummaryDelYear();
        setStarDate();
        setLastDate();
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

    function getFirstDayOfMonth(year, month) {
        return new Date(year, month, 1);
    }

    function setStarDate() {
        const date = new Date();
        const firstDay = getFirstDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );

        const firstMonth = getFirstDayOfMonth(
            date.getFullYear(),
            0,
        );
        ele('Start_Date').setValue(firstDay);
        ele('Start_Month').setValue(firstMonth);
        ele('Start_Year').setValue(firstMonth);
    }

    function getLastDayOfMonth(year, month) {
        return new Date(year, month + 1, 0);
    };

    function setLastDate() {
        const date = new Date();
        const LastDay = getLastDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );

        const LastMonth = getLastDayOfMonth(
            date.getFullYear(),
            11,
        );
        ele('Stop_Date').setValue(LastDay);
        ele('Stop_Month').setValue(LastMonth);
        ele('Stop_Year').setValue(LastMonth);
    };

    function setChart(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
        //ele(tableName).filterByAll();
    };

    //Daily

    function loadSummaryTripDay(btn) {
        webix.extend(ele("chart_sumarytrip_day"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarytrip_day").clearAll();
        ele("chart_sumarytrip_day").disable();
        ele("chart_sumarytrip_day").showProgress({
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
                if (data[0].total_trip_day > 0) {
                    setChart("chart_sumarytrip_day", data);
                    ele("total_trip_day").setValue(data[0].total_trip_day);
                    ele("completed_day").setValue(data[0].completed_day);
                    ele("in_transit_day").setValue(data[0].in_transit_day);
                    ele("pending_day").setValue(data[0].pending_day);
                }
                else {
                    ele("chart_sumarytrip_day").clearAll();
                    ele("total_trip_day").setValue('');
                    ele("completed_day").setValue('');
                    ele("in_transit_day").setValue('');
                    ele("pending_day").setValue('');
                }
            }, btn);
            ele("chart_sumarytrip_day").enable();
        }, 500);
    };


    function loadSummaryDelDay(btn) {
        webix.extend(ele("chart_sumarydel_day"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarydel_day").clearAll();
        ele("chart_sumarydel_day").disable();
        ele("chart_sumarydel_day").showProgress({
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
                if (data[0].total_del_day > 0) {
                    setChart("chart_sumarydel_day", data);
                    ele("total_del_day").setValue(data[0].total_del_day);
                    ele("ontime_day").setValue(data[0].ontime_day);
                    ele("early_day").setValue(data[0].early_day);
                    ele("delay_day").setValue(data[0].delay_day);
                    ele("waiting_day").setValue(data[0].waiting_day);
                }
                else {
                    ele("chart_sumarydel_day").clearAll();
                    ele("total_del_day").setValue('');
                    ele("ontime_day").setValue('');
                    ele("early_day").setValue('');
                    ele("delay_day").setValue('');
                    ele("waiting_day").setValue('');
                }
            }, btn);
            ele("chart_sumarydel_day").enable();
        }, 500);
    };

    //Date

    function loadSummaryTripDate(btn) {
        webix.extend(ele("chart_sumarytrip_date"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarytrip_date").clearAll();
        ele("chart_sumarytrip_date").disable();
        ele("chart_sumarytrip_date").showProgress({
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
                    setChart("chart_sumarytrip_date", data);
                    ele("total_trip_date").setValue(data[0].total);
                    ele("completed_date").setValue(data[0].total_completed_date);
                    ele("in_transit_date").setValue(data[0].total_in_transit_date);
                    ele("pending_date").setValue(data[0].total_pending_date);
                }
                else {
                    ele("chart_sumarytrip_date").clearAll();
                    ele("total_trip_date").setValue('');
                    ele("completed_date").setValue('');
                    ele("in_transit_date").setValue('');
                    ele("pending_date").setValue('');
                }
            }, btn);
            ele("chart_sumarytrip_date").enable();
        }, 500);
    };


    function loadSummaryDelDate(btn) {
        webix.extend(ele("chart_sumarydel_date"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarydel_date").clearAll();
        ele("chart_sumarydel_date").disable();
        ele("chart_sumarydel_date").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form2").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 4, function (json) {
                var data = json.data;
                var data_total = data.Total;
                if (data_total[0].total > 0) {
                    setChart("chart_sumarydel_date", data.Sum_Del_Date);
                    ele("total_del_date").setValue(data_total[0].total);
                    ele("ontime_date").setValue(data_total[0].total_ontime_date);
                    ele("early_date").setValue(data_total[0].total_early_date);
                    ele("delay_date").setValue(data_total[0].total_delay_date);
                    ele("waiting_date").setValue(data_total[0].total_waiting_date);
                }
                else {
                    ele("chart_sumarydel_date").clearAll();
                    ele("total_del_date").setValue('');
                    ele("ontime_date").setValue('');
                    ele("early_date").setValue('');
                    ele("delay_date").setValue('');
                    ele("waiting_date").setValue('');
                }
            }, btn);
            ele("chart_sumarydel_date").enable();
        }, 500);
    };

    //Month

    function loadSummaryTripMonth(btn) {
        webix.extend(ele("chart_sumarytrip_month"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarytrip_month").clearAll();
        ele("chart_sumarytrip_month").disable();
        ele("chart_sumarytrip_month").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form3").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 5, function (json) {
                var data = json.data;
                if (data.length > 0) {
                    setChart("chart_sumarytrip_month", data);
                    ele("total_trip_month").setValue(data[0].total);
                    ele("completed_month").setValue(data[0].total_completed_month);
                    ele("in_transit_month").setValue(data[0].total_in_transit_month);
                    ele("pending_month").setValue(data[0].total_pending_month);
                }
                else {
                    ele("chart_sumarytrip_month").clearAll();
                    ele("total_trip_month").setValue('');
                    ele("completed_month").setValue('');
                    ele("in_transit_month").setValue('');
                    ele("pending_month").setValue('');
                }
            }, btn);
            ele("chart_sumarytrip_month").enable();
        }, 500);
    };

    function loadSummaryDelMonth(btn) {
        webix.extend(ele("chart_sumarydel_month"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarydel_month").clearAll();
        ele("chart_sumarydel_month").disable();
        ele("chart_sumarydel_month").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form3").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 6, function (json) {
                var data = json.data;
                var data_total = data.Total;
                //console.log(data.Sum_Del_Date);
                //setChart("chart_sumarydel_month", data.Sum_Del_Date);
                if (data_total[0].total > 0) {
                    setChart("chart_sumarydel_month", data.Sum_Del_Date);
                    ele("total_del_month").setValue(data_total[0].total);
                    ele("ontime_month").setValue(data_total[0].total_ontime_month);
                    ele("early_month").setValue(data_total[0].total_early_month);
                    ele("delay_month").setValue(data_total[0].total_delay_month);
                    ele("waiting_month").setValue(data_total[0].total_waiting_month);
                }
                else {
                    ele("chart_sumarydel_month").clearAll();
                    ele("total_del_month").setValue('');
                    ele("ontime_month").setValue('');
                    ele("early_month").setValue('');
                    ele("delay_month").setValue('');
                    ele("waiting_month").setValue('');
                }
            }, btn);
            ele("chart_sumarydel_month").enable();
        }, 500);
    };

    //Year

    function loadSummaryTripYear(btn) {
        webix.extend(ele("chart_sumarytrip_year"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarytrip_year").clearAll();
        ele("chart_sumarytrip_year").disable();
        ele("chart_sumarytrip_year").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form4").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 7, function (json) {
                var data = json.data;
                if (data.length > 0) {
                    setChart("chart_sumarytrip_year", data);
                    ele("total_trip_year").setValue(data[0].total);
                    ele("completed_year").setValue(data[0].total_completed_year);
                    ele("in_transit_year").setValue(data[0].total_in_transit_year);
                    ele("pending_year").setValue(data[0].total_pending_year);
                }
                else {
                    ele("chart_sumarytrip_year").clearAll();
                    ele("total_trip_year").setValue('');
                    ele("completed_year").setValue('');
                    ele("in_transit_year").setValue('');
                    ele("pending_year").setValue('');
                }
            }, btn);
            ele("chart_sumarytrip_year").enable();
        }, 500);
    };

    function loadSummaryDelYear(btn) {
        webix.extend(ele("chart_sumarydel_year"), webix.ProgressBar);
        var delay = 500;
        ele("chart_sumarydel_year").clearAll();
        ele("chart_sumarydel_year").disable();
        ele("chart_sumarydel_year").showProgress({
            type: "bottom",
            delay: delay,
            hide: true
        });
        setTimeout(function () {
            var obj1 = ele("form_customer").getValues();
            var obj2 = ele("form4").getValues();
            var obj = { ...obj1, ...obj2 };
            ajax(fd, obj, 8, function (json) {
                var data = json.data;
                var data_total = data.Total;
                if (data_total[0].total > 0) {
                    setChart("chart_sumarydel_year", data.Sum_Del_Date);
                    ele("total_del_year").setValue(data_total[0].total);
                    ele("ontime_year").setValue(data_total[0].total_ontime_year);
                    ele("early_year").setValue(data_total[0].total_early_year);
                    ele("delay_year").setValue(data_total[0].total_delay_year);
                    ele("waiting_year").setValue(data_total[0].total_waiting_year);
                }
                else {
                    ele("chart_sumarydel_year").clearAll();
                    ele("total_del_year").setValue('');
                    ele("ontime_year").setValue('');
                    ele("early_year").setValue('');
                    ele("delay_year").setValue('');
                    ele("waiting_year").setValue('');
                }
            }, btn);
            ele("chart_sumarydel_year").enable();
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
    };


    return {
        view: "scrollview",
        scroll: "native-y",
        id: "header_SummaryTrip",
        body:
        {
            id: "SummaryTrip_id",
            type: "clean",
            rows:
                [
                    { view: "template", template: "Summary Report", type: "header" },
                    {
                        view: "form", scroll: false, id: $n('form_customer'),
                        elements:
                            [
                                {
                                    cols: [
                                        vw1('combo', 'Customer_Code', 'Site', {
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
                                                    loadSummaryTripDay();
                                                    loadSummaryDelDay();
                                                    loadSummaryTripDate();
                                                    loadSummaryDelDate();
                                                    loadSummaryTripMonth();
                                                    loadSummaryDelMonth();
                                                    loadSummaryTripYear();
                                                    loadSummaryDelYear();
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
                                                            loadSummaryTripDay();
                                                            loadSummaryDelDay();
                                                            loadSummaryTripDate();
                                                            loadSummaryDelDate();
                                                            loadSummaryTripMonth();
                                                            loadSummaryDelMonth();
                                                            loadSummaryTripYear();
                                                            loadSummaryDelYear();
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
                    //Daily
                    {
                        view: "form", scroll: false, id: $n('form1'),
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Daily results",
                                    body: {
                                        rows: [
                                            {
                                                cols:
                                                    [
                                                        vw1("datepicker", 'Operation_Date', "Operation Date", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat, required: false, width: 200, }),
                                                        {
                                                            rows: [
                                                                {},
                                                                vw1('button', 'find', 'Find', {
                                                                    width: 120, css: "webix_primary",
                                                                    icon: "mdi mdi-magnify", type: "icon",
                                                                    tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                    on:
                                                                    {
                                                                        onItemClick: function () {
                                                                            loadSummaryTripDay();
                                                                            loadSummaryDelDay();
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
                                                    vw1("button", 'btnExport', "Export Report", {
                                                        width: 120, css: "webix_orange",
                                                        icon: "mdi mdi-table-arrow-down", type: "icon",
                                                        tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                        on: {
                                                            onItemClick: function () {
                                                                var dataT1 = ele("chart_sumarytrip_day");
                                                                if (dataT1.count() != 0) {
                                                                    var Operation_Date = ele('Operation_Date').getValue();
                                                                    var Customer_Code = ele('Customer_Code').getValue();
                                                                    var temp = window.open(fd + "?type=51" + "&Operation_Date=" + Operation_Date + "&Customer_Code=" + Customer_Code);
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
                                                cols: [
                                                    {
                                                        view: "fieldset",
                                                        label: "Summary of trip",
                                                        body: {
                                                            cols: [
                                                                {
                                                                    rows: [
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Total :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("total_trip_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Completed :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("completed_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "In-Transit :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("in_transit_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Pending :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("pending_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    view: "chart",
                                                                    id: $n('chart_sumarytrip_day'),
                                                                    //width:900,
                                                                    height: 250,
                                                                    type: "bar",
                                                                    barWidth: 25,
                                                                    radius: 2,
                                                                    gradient: "rising",
                                                                    xAxis: {
                                                                        template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.date}</div>`; },
                                                                    },
                                                                    yAxis: {
                                                                        start: 0,
                                                                        step: 10,
                                                                        end: 50,
                                                                        template: function (obj) {
                                                                            return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                        }
                                                                    },
                                                                    legend: {
                                                                        values: [{ text: "Completed", color: "#a7ee70" }, { text: "In-Transit", color: "#eee170" }, { text: "Pending", color: "#d6d6d6" }],
                                                                        valign: "middle",
                                                                        align: "right",
                                                                        width: 90,
                                                                        layout: "y"
                                                                    },
                                                                    series: [
                                                                        {
                                                                            value: "#completed_day#",
                                                                            color: "#a7ee70",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.completed_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "Completed : #completed_day#"
                                                                            }
                                                                        },
                                                                        {
                                                                            value: "#in_transit_day#",
                                                                            color: "#eee170",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.in_transit_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "In-Transit : #in_transit_day#"
                                                                            }
                                                                        },
                                                                        {
                                                                            value: "#pending_day#",
                                                                            color: "#d6d6d6",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.pending_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "Pending : #pending_day#"
                                                                            }
                                                                        }
                                                                    ],
                                                                }
                                                            ]
                                                        },
                                                    },
                                                    {
                                                        view: "fieldset",
                                                        label: "Summary of delivery",
                                                        body: {
                                                            cols: [
                                                                {
                                                                    rows: [
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Total :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("total_del_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "On Time :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("ontime_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Early :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("early_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Delay :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("delay_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Waiting :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("waiting_day"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    view: "chart",
                                                                    id: $n('chart_sumarydel_day'),
                                                                    //width:900,
                                                                    height: 250,
                                                                    type: "bar",
                                                                    barWidth: 25,
                                                                    radius: 2,
                                                                    gradient: "rising",
                                                                    xAxis: {
                                                                        template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.date}</div>`; },
                                                                    },
                                                                    yAxis: {
                                                                        start: 0,
                                                                        step: 10,
                                                                        end: 50,
                                                                        template: function (obj) {
                                                                            return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                            //return (obj%20?"":obj)
                                                                        }
                                                                    },
                                                                    legend: {
                                                                        values: [{ text: "On Time", color: "#a7ee70" }, { text: "Early", color: "#36abee" }, { text: "Delay", color: "#ee3648" }, { text: "Waiting", color: "#d6d6d6" }],
                                                                        valign: "middle",
                                                                        align: "right",
                                                                        width: 90,
                                                                        layout: "y"
                                                                    },
                                                                    series: [
                                                                        {
                                                                            value: "#ontime_day#",
                                                                            color: "#a7ee70",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.ontime_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "On Time : #ontime_day#"
                                                                            }
                                                                        },
                                                                        {
                                                                            value: "#early_day#",
                                                                            color: "#36abee",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.early_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "Early : #early_day#"
                                                                            }
                                                                        },
                                                                        {
                                                                            value: "#delay_day#",
                                                                            color: "#ee3648",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.delay_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "Delay : #delay_day#"
                                                                            }
                                                                        },
                                                                        {
                                                                            value: "#waiting_day#",
                                                                            color: "#d6d6d6",
                                                                            label: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.waiting_day}</div>`; },
                                                                            tooltip: {
                                                                                template: "Waiting : #waiting_day#"
                                                                            }
                                                                        }
                                                                    ],
                                                                }
                                                            ]
                                                        },
                                                    },
                                                ]
                                            },

                                        ]
                                    }
                                },
                            ]
                    },

                    //Date

                    {
                        view: "form", scroll: false, id: $n('form2'),
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Day results",
                                    body: {
                                        rows: [
                                            {
                                                cols:
                                                    [
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
                                                                            loadSummaryTripDate();
                                                                            loadSummaryDelDate();
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
                                                                var dataT1 = ele("chart_sumarytrip_date");
                                                                if (dataT1.count() != 0) {
                                                                    var Start_Date = ele('Start_Date').getValue();
                                                                    var Stop_Date = ele('Stop_Date').getValue();
                                                                    var Customer_Code = ele('Customer_Code').getValue();
                                                                    var temp = window.open(fd + "?type=52" + "&Start_Date=" + Start_Date + "&Stop_Date=" + Stop_Date + "&Customer_Code=" + Customer_Code);
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
                                                cols: [
                                                    {
                                                        view: "fieldset",
                                                        label: "Summary of trip",
                                                        body: {
                                                            cols: [
                                                                {
                                                                    rows: [
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Total :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("total_trip_date"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Completed :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("completed_date"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "In-Transit :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("in_transit_date"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Pending :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("pending_date"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    view: "scrollview",
                                                                    id: "scrollview",
                                                                    scroll: "x",
                                                                    //height: 160,
                                                                    //width: 150, 
                                                                    body: {
                                                                        view: "chart",
                                                                        id: $n('chart_sumarytrip_date'),
                                                                        width: 2000,
                                                                        height: 250,
                                                                        type: "bar",
                                                                        barWidth: 25,
                                                                        radius: 2,
                                                                        gradient: "rising",
                                                                        xAxis: {
                                                                            template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.date}<br>${obj.month}</div>`; },
                                                                        },
                                                                        yAxis: {
                                                                            start: 0,
                                                                            step: 10,
                                                                            end: 50,
                                                                            template: function (obj) {
                                                                                return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                                //return (obj%20?"":obj)
                                                                            }
                                                                        },
                                                                        legend: {
                                                                            values: [{ text: "Completed", color: "#a7ee70" }, { text: "In-Transit", color: "#eee170" }, { text: "Pending", color: "#d6d6d6" }],
                                                                            valign: "middle",
                                                                            align: "center",
                                                                            width: 90,
                                                                            layout: "x"
                                                                        },
                                                                        series: [
                                                                            {
                                                                                value: "#completed_date#",
                                                                                color: "#a7ee70",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.completed_date}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Completed : #completed_date#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#in_transit_date#",
                                                                                color: "#eee170",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.in_transit_date}</div>`; },
                                                                                tooltip: {
                                                                                    template: "In-Transit : #in_transit_date#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#pending_date#",
                                                                                color: "#d6d6d6",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.pending_date}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Pending : #pending_date#"
                                                                                }
                                                                            }
                                                                        ],
                                                                    }
                                                                },
                                                            ]
                                                        },
                                                    },
                                                ]
                                            },
                                            {
                                                view: "fieldset",
                                                label: "Summary of delivery",
                                                body: {
                                                    cols: [
                                                        {
                                                            rows: [
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Total :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("total_del_date"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "On Time :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("ontime_date"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Early :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("early_date"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Delay :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("delay_date"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Waiting :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("waiting_date"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            //height: 160,
                                                            //width: 150, 
                                                            body: {
                                                                view: "chart",
                                                                id: $n('chart_sumarydel_date'),
                                                                width: 2000,
                                                                height: 250,
                                                                type: "bar",
                                                                barWidth: 20,
                                                                radius: 2,
                                                                gradient: "rising",
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.date}<br>${obj.month}</div>`; },
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    step: 10,
                                                                    end: 50,
                                                                    template: function (obj) {
                                                                        return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                        //return (obj%20?"":obj)
                                                                    }
                                                                },
                                                                legend: {
                                                                    values: [{ text: "On Time", color: "#a7ee70" }, { text: "Early", color: "#36abee" }, { text: "Delay", color: "#ee3648" }, { text: "Waiting", color: "#d6d6d6" }],
                                                                    valign: "middle",
                                                                    align: "center",
                                                                    width: 90,
                                                                    layout: "x"
                                                                },
                                                                series: [
                                                                    {
                                                                        value: "#ontime_date#",
                                                                        color: "#a7ee70",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.ontime_date}</div>`; },
                                                                        tooltip: {
                                                                            template: "On Time : #ontime_date#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#early_date#",
                                                                        color: "#36abee",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.early_date}</div>`; },
                                                                        tooltip: {
                                                                            template: "Early : #early_date#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#delay_date#",
                                                                        color: "#ee3648",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.delay_date}</div>`; },
                                                                        tooltip: {
                                                                            template: "Delay : #delay_date#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#waiting_date#",
                                                                        color: "#d6d6d6",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.waiting_date}</div>`; },
                                                                        tooltip: {
                                                                            template: "Waiting : #waiting_date#"
                                                                        }
                                                                    }
                                                                ],
                                                                //data: data_summarydel
                                                            }
                                                        },
                                                    ]
                                                },
                                            },

                                        ]
                                    }
                                },
                            ]
                    },

                    //Month

                    {
                        view: "form", scroll: false, id: $n('form3'),
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Monthly results",
                                    body: {
                                        rows: [
                                            {
                                                cols:
                                                    [
                                                        vw1("datepicker", 'Start_Month', "Start (เดือนที่เริ่ม)", { type: "month", format: "%M-%Y", required: false, width: 200, }),
                                                        vw1("datepicker", 'Stop_Month', "End (เดือนที่สิ้นสุด)", { type: "month", format: "%M-%Y", required: false, width: 200, }),
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
                                                                            loadSummaryTripMonth();
                                                                            loadSummaryDelMonth();
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
                                                    vw1("button", 'btnExport2', "Export Report", {
                                                        width: 120, css: "webix_orange",
                                                        icon: "mdi mdi-table-arrow-down", type: "icon",
                                                        tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                        on: {
                                                            onItemClick: function () {
                                                                var dataT1 = ele("chart_sumarytrip_month");
                                                                if (dataT1.count() != 0) {
                                                                    var Start_Month = ele('Start_Month').getValue();
                                                                    var Stop_Month = ele('Stop_Month').getValue();
                                                                    var Customer_Code = ele('Customer_Code').getValue();
                                                                    var temp = window.open(fd + "?type=53" + "&Start_Month=" + Start_Month + "&Stop_Month=" + Stop_Month + "&Customer_Code=" + Customer_Code);
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
                                                cols: [
                                                    {
                                                        view: "fieldset",
                                                        label: "Summary of trip",
                                                        //hidden:1,
                                                        body: {
                                                            cols: [
                                                                {
                                                                    rows: [
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Total :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("total_trip_month"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Completed :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("completed_month"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "In-Transit :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("in_transit_month"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Pending :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("pending_month"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    view: "scrollview",
                                                                    id: "scrollview",
                                                                    scroll: "x",
                                                                    //height: 160,
                                                                    //width: 150, 
                                                                    body: {
                                                                        view: "chart",
                                                                        id: $n('chart_sumarytrip_month'),
                                                                        width: 1500,
                                                                        height: 300,
                                                                        type: "bar",
                                                                        barWidth: 25,
                                                                        radius: 2,
                                                                        gradient: "rising",
                                                                        xAxis: {
                                                                            template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.month}<br>${obj.year}</div>`; },
                                                                        },
                                                                        yAxis: {
                                                                            start: 0,
                                                                            step: 100,
                                                                            end: 1000,
                                                                            template: function (obj) {
                                                                                return (obj % 200 ? "" : `<div style="font-size:10px; text-align:center;">${obj}</div>`)
                                                                            }
                                                                        },
                                                                        legend: {
                                                                            values: [{ text: "Completed", color: "#a7ee70" }, { text: "In-Transit", color: "#eee170" }, { text: "Pending", color: "#d6d6d6" }],
                                                                            valign: "middle",
                                                                            align: "center",
                                                                            width: 90,
                                                                            layout: "x"
                                                                        },
                                                                        series: [
                                                                            {
                                                                                value: "#completed_month#",
                                                                                color: "#a7ee70",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.completed_month}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Completed : #completed_month#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#in_transit_month#",
                                                                                color: "#eee170",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.in_transit_month}</div>`; },
                                                                                tooltip: {
                                                                                    template: "In-Transit : #in_transit_month#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#pending_month#",
                                                                                color: "#d6d6d6",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.pending_month}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Pending : #pending_month#"
                                                                                }
                                                                            }
                                                                        ],
                                                                    }
                                                                },
                                                            ]
                                                        },
                                                    },
                                                ]
                                            },
                                            {
                                                view: "fieldset",
                                                label: "Summary of delivery",
                                                body: {
                                                    cols: [
                                                        {
                                                            rows: [
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Total :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("total_del_month"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "On Time :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("ontime_month"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Early :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("early_month"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Delay :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("delay_month"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Waiting :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("waiting_month"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            //height: 160,
                                                            //width: 150, 
                                                            body: {
                                                                view: "chart",
                                                                id: $n('chart_sumarydel_month'),
                                                                width: 1500,
                                                                height: 300,
                                                                type: "bar",
                                                                barWidth: 20,
                                                                radius: 2,
                                                                gradient: "rising",
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.month}<br>${obj.year}</div>`; },
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    step: 100,
                                                                    end: 1000,
                                                                    template: function (obj) {
                                                                        return (obj % 200 ? "" : `<div style="font-size:10px; text-align:center;">${obj}</div>`)
                                                                    }
                                                                },
                                                                legend: {
                                                                    values: [{ text: "On Time", color: "#a7ee70" }, { text: "Early", color: "#36abee" }, { text: "Delay", color: "#ee3648" }, { text: "Waiting", color: "#d6d6d6" }],
                                                                    valign: "middle",
                                                                    align: "center",
                                                                    width: 90,
                                                                    layout: "x"
                                                                },
                                                                series: [
                                                                    {
                                                                        value: "#ontime_month#",
                                                                        color: "#a7ee70",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.ontime_month}</div>`; },
                                                                        tooltip: {
                                                                            template: "On Time : #ontime_month#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#early_month#",
                                                                        color: "#36abee",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.early_month}</div>`; },
                                                                        tooltip: {
                                                                            template: "Early : #early_month#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#delay_month#",
                                                                        color: "#ee3648",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.delay_month}</div>`; },
                                                                        tooltip: {
                                                                            template: "Delay : #delay_month#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#waiting_month#",
                                                                        color: "#d6d6d6",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.waiting_month}</div>`; },
                                                                        tooltip: {
                                                                            template: "Waiting : #waiting_month#"
                                                                        }
                                                                    }
                                                                ],
                                                            }
                                                        },
                                                    ]
                                                },
                                            },

                                        ]
                                    }
                                },
                            ]
                    },

                    //Year

                    {
                        view: "form", scroll: false, id: $n('form4'),
                        elements:
                            [
                                {
                                    view: "fieldset",
                                    label: "Yearly results",
                                    body: {
                                        rows: [
                                            {
                                                cols:
                                                    [
                                                        vw1("datepicker", 'Start_Year', "Start (ปีที่เริ่ม)", { type: "year", format: "%Y", required: false, width: 200, }),
                                                        vw1("datepicker", 'Stop_Year', "End (ปีที่สิ้นสุด)", { type: "year", format: "%Y", required: false, width: 200, }),
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
                                                                            loadSummaryTripYear();
                                                                            loadSummaryDelYear();
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
                                                    vw1("button", 'btnExport4', "Export Report", {
                                                        width: 120, css: "webix_orange",
                                                        icon: "mdi mdi-table-arrow-down", type: "icon",
                                                        tooltip: { template: "ดาวน์โหลดข้อมูล", dx: 10, dy: 15 },
                                                        on: {
                                                            onItemClick: function () {
                                                                var dataT1 = ele("chart_sumarytrip_year");
                                                                if (dataT1.count() != 0) {
                                                                    var Start_Year = ele('Start_Year').getValue();
                                                                    var Stop_Year = ele('Stop_Year').getValue();
                                                                    var Customer_Code = ele('Customer_Code').getValue();
                                                                    var temp = window.open(fd + "?type=54" + "&Start_Year=" + Start_Year + "&Stop_Year=" + Stop_Year + "&Customer_Code=" + Customer_Code);
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
                                                cols: [
                                                    {
                                                        view: "fieldset",
                                                        label: "Summary of trip",
                                                        body: {
                                                            cols: [
                                                                {
                                                                    rows: [
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Total :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("total_trip_year"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Completed :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("completed_year"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "In-Transit :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("in_transit_year"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                        {
                                                                            height: 35,
                                                                            cols: [
                                                                                {
                                                                                    view: "label", label: "Pending :", type: "clean", width: 80,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                                {
                                                                                    view: "label", id: $n("pending_year"), label: "", type: "clean", width: 60,
                                                                                    css: { "text-align": "left", "padding-left": "5px" },
                                                                                },
                                                                            ]
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    view: "scrollview",
                                                                    id: "scrollview",
                                                                    scroll: "x",
                                                                    //height: 160,
                                                                    //width: 150, 
                                                                    body: {
                                                                        view: "chart",
                                                                        id: $n('chart_sumarytrip_year'),
                                                                        //width: 1500,
                                                                        height: 300,
                                                                        type: "bar",
                                                                        barWidth: 25,
                                                                        radius: 2,
                                                                        gradient: "rising",
                                                                        xAxis: {
                                                                            template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.year}</div>`; },
                                                                        },
                                                                        yAxis: {
                                                                            start: 0,
                                                                            step: 1000,
                                                                            end: 5000,
                                                                            template: function (obj) {
                                                                                return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                            }
                                                                        },
                                                                        legend: {
                                                                            values: [{ text: "Completed", color: "#a7ee70" }, { text: "In-Transit", color: "#eee170" }, { text: "Pending", color: "#d6d6d6" }],
                                                                            valign: "middle",
                                                                            align: "center",
                                                                            width: 90,
                                                                            layout: "x"
                                                                        },
                                                                        series: [
                                                                            {
                                                                                value: "#completed_year#",
                                                                                color: "#a7ee70",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.completed_year}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Completed : #completed_year#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#in_transit_year#",
                                                                                color: "#eee170",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.in_transit_year}</div>`; },
                                                                                tooltip: {
                                                                                    template: "In-Transit : #in_transit_year#"
                                                                                }
                                                                            },
                                                                            {
                                                                                value: "#pending_year#",
                                                                                color: "#d6d6d6",
                                                                                label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.pending_year}</div>`; },
                                                                                tooltip: {
                                                                                    template: "Pending : #pending_year#"
                                                                                }
                                                                            }
                                                                        ],
                                                                    }
                                                                },
                                                            ]
                                                        },
                                                    },
                                                ]
                                            },
                                            {
                                                view: "fieldset",
                                                label: "Summary of delivery",
                                                body: {
                                                    cols: [
                                                        {
                                                            rows: [
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Total :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("total_del_year"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px", "background-color": "#def0fc" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "On Time :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("ontime_year"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Early :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("early_year"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Delay :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("delay_year"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                                {
                                                                    height: 35,
                                                                    cols: [
                                                                        {
                                                                            view: "label", label: "Waiting :", type: "clean", width: 80,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                        {
                                                                            view: "label", id: $n("waiting_year"), label: "", type: "clean", width: 60,
                                                                            css: { "text-align": "left", "padding-left": "5px" },
                                                                        },
                                                                    ]
                                                                },
                                                            ]
                                                        },
                                                        {
                                                            view: "scrollview",
                                                            id: "scrollview",
                                                            scroll: "x",
                                                            //height: 160,
                                                            //width: 150, 
                                                            body: {
                                                                view: "chart",
                                                                id: $n('chart_sumarydel_year'),
                                                                //width: 1500,
                                                                height: 300,
                                                                type: "bar",
                                                                barWidth: 20,
                                                                radius: 2,
                                                                gradient: "rising",
                                                                xAxis: {
                                                                    template: function (obj) { return `<div style="font-size:10px; text-align:center;">${obj.year}</div>`; },
                                                                },
                                                                yAxis: {
                                                                    start: 0,
                                                                    step: 1000,
                                                                    end: 5000,
                                                                    template: function (obj) {
                                                                        return `<div style="font-size:10px; text-align:center;">${obj}</div>`;
                                                                    }
                                                                },
                                                                legend: {
                                                                    values: [{ text: "On Time", color: "#a7ee70" }, { text: "Early", color: "#36abee" }, { text: "Delay", color: "#ee3648" }, { text: "Waiting", color: "#d6d6d6" }],
                                                                    valign: "middle",
                                                                    align: "center",
                                                                    width: 90,
                                                                    layout: "x"
                                                                },
                                                                series: [
                                                                    {
                                                                        value: "#ontime_year#",
                                                                        color: "#a7ee70",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.ontime_year}</div>`; },
                                                                        tooltip: {
                                                                            template: "On Time : #ontime_year#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#early_year#",
                                                                        color: "#36abee",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.early_year}</div>`; },
                                                                        tooltip: {
                                                                            template: "Early : #early_year#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#delay_year#",
                                                                        color: "#ee3648",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.delay_year}</div>`; },
                                                                        tooltip: {
                                                                            template: "Delay : #delay_year#"
                                                                        }
                                                                    },
                                                                    {
                                                                        value: "#waiting_year#",
                                                                        color: "#d6d6d6",
                                                                        label: function (obj) { return `<div style="font-size:8px; text-align:center;">${obj.waiting_year}</div>`; },
                                                                        tooltip: {
                                                                            template: "Waiting : #waiting_year#"
                                                                        }
                                                                    }
                                                                ],
                                                            }
                                                        },
                                                    ]
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