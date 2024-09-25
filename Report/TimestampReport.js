var header_TimestampReport = function () {
    var menuName = "TimestampReport_", fd = "Report/" + menuName + "data.php";

    function init() {
        webix.extend($$("TimestampReport_id"), webix.ProgressBar);
        refreshAt(0, 0, 0); //Will refresh the page at 00:00
        setStartDate();
        setLastDate();
        show_progress_icon(2000);
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
        setTimeout(function () { window.location.reload(true); }, timeout);
    };

    function setChart(tableName, data) {
        if (!ele(tableName)) return;
        ele(tableName).clearAll();
        ele(tableName).parse(data);
    };


    function getFirstDayOfMonth(year, month) {
        return new Date(year, month, 1);
    };

    function setStartDate() {
        const date = new Date();
        const firstDay = getFirstDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );
        ele('Start_Date').setValue(firstDay);
    };

    function getLastDayOfMonth(year, month) {
        return new Date(year, month + 1, 0);
    };

    function setLastDate() {
        const date = new Date();
        const LastDay = getLastDayOfMonth(
            date.getFullYear(),
            date.getMonth(),
        );
        ele('Stop_Date').setValue(LastDay);
    };

    function show_progress_icon(delay) {
        $$("TimestampReport_id").disable();
        $$("TimestampReport_id").showProgress({
            delay: delay,
            hide: true
        });
        loadData_Bar();
        loadData_Total();
        setTimeout(function () {
            $$("TimestampReport_id").enable();
        }, delay);
    };

    function loadData_Total(btn) {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 1, function (json) {
            var Auto = json.data.Auto;
            var Manual = json.data.Manual;
            Array.prototype.push.apply(Auto, Manual);
            var data = Auto;
            setChart('chart_total', data);
            setChart('chart_pickup', data);
            setChart('chart_delivery', data);
        });
    };

    function loadData_Bar(btn) {
        var obj = ele('form1').getValues();
        ajax(fd, obj, 2, function (json) {
            var data = json.data;
            setChart('chart_Bar', data);
        }, null,
            function (json) {
            });
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
        id: "header_TimestampReport",
        body:
        {
            id: "TimestampReport_id",
            type: "line",
            rows:
                [
                    { view: "template", template: "Timestamp Report", type: "header" },
                    {
                        paddingY: 30,
                        view: "form", scroll: false, id: $n('form1'),
                        elements: [
                            {
                                rows: [
                                    {
                                        cols: [
                                            vw1('combo', 'Customer_Code', 'Site', {
                                                required: false,
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
                                            vw1("datepicker", 'Start_Date', "Start Date (วันเริ่ม)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat }),
                                            vw1("datepicker", 'Stop_Date', "Stop Date (วันหยุด)", { value: dayjs().format("YYYY-MM-DD"), stringResult: true, ...datatableDateFormat }),
                                            {
                                                rows: [
                                                    {},
                                                    {
                                                        cols: [

                                                            vw1('button', 'find', 'Find', {
                                                                width: 120, css: "webix_primary",
                                                                icon: "mdi mdi-magnify", type: "icon",
                                                                tooltip: { template: "ค้นหา", dx: 10, dy: 15 },
                                                                on: {
                                                                    onItemClick: function (id, e) {
                                                                        ele("label_Total").setValue("0");
                                                                        ele("label_Pick_Total").setValue("0");
                                                                        ele("label_Delivery_Total").setValue("0");

                                                                        ele("label_Total_Auto").setValue("0");
                                                                        ele("label_Pick_Auto").setValue("0");
                                                                        ele("label_Delivery_Auto").setValue("0");

                                                                        ele("label_Total_Manual").setValue("0");
                                                                        ele("label_Pick_Manual").setValue("0");
                                                                        ele("label_Delivery_Manual").setValue("0");
                                                                        show_progress_icon(2000);
                                                                    }
                                                                }
                                                            }),
                                                        ]
                                                    },
                                                ]
                                            },
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
                                                                setStartDate();
                                                                setLastDate();
                                                                ele('Customer_Code').setValue('');
                                                                reload_options_customer();

                                                                ele("label_Total").setValue("0");
                                                                ele("label_Pick_Total").setValue("0");
                                                                ele("label_Delivery_Total").setValue("0");

                                                                ele("label_Total_Auto").setValue("0");
                                                                ele("label_Pick_Auto").setValue("0");
                                                                ele("label_Delivery_Auto").setValue("0");

                                                                ele("label_Total_Manual").setValue("0");
                                                                ele("label_Pick_Manual").setValue("0");
                                                                ele("label_Delivery_Manual").setValue("0");
                                                                show_progress_icon(2000);
                                                            }
                                                        }
                                                    }),
                                                ]
                                            },
                                            {},
                                            {}
                                        ]
                                    },
                                ]
                            },
                        ]
                    },
                    {
                        cols: [
                            {
                                rows: [
                                    {
                                        template: "<div style='width:100%;text-align:center;font-size:14px;'>TOTAL</div>",
                                        height: 30
                                    },
                                    {
                                        view: "chart",
                                        id: $n("chart_total"),
                                        height: 300,
                                        type: "pie",
                                        value: "#Percent_All#",
                                        color: "#color#",
                                        //label: "#Goal#",
                                        //pieInnerText: "#Percent_All#",
                                        pieInnerText: function (obj) { return `<span style='color:white'>${obj.Percent_All}</span>`; },
                                        shadow: 0,
                                        legend: {
                                            values: [{ text: "Manual", color: "#e8591c" }, { text: "Auto", color: "#30b358" }],
                                            valign: "middle",
                                            align: "top",
                                            width: 90,
                                            layout: "x"
                                        },
                                    },
                                ]
                            },
                            {
                                rows: [
                                    {
                                        template: "<div style='width:100%;text-align:center;font-size:14px;'>PICK UP</div>",
                                        height: 30
                                    },
                                    {
                                        view: "chart",
                                        id: $n("chart_pickup"),
                                        type: "pie",
                                        value: "#Percent_Pickup#",
                                        color: "#color#",
                                        //label: "#Goal#",
                                        // pieInnerText: "#Percent_Pickup#",
                                        pieInnerText: function (obj) { return `<span style='color:white'>${obj.Percent_Pickup}</span>`; },
                                        shadow: 0,
                                        legend: {
                                            values: [{ text: "Manual", color: "#e8591c" }, { text: "Auto", color: "#30b358" }],
                                            valign: "middle",
                                            align: "top",
                                            width: 90,
                                            layout: "x"
                                        },
                                    },
                                ]
                            },
                            {
                                rows: [
                                    {
                                        template: "<div style='width:100%;text-align:center;font-size:14px;'>DELIVERY</div>",
                                        height: 30
                                    },
                                    {
                                        view: "chart",
                                        id: $n("chart_delivery"),
                                        type: "pie",
                                        value: "#Percent_Del#",
                                        color: "#color#",
                                        //label: "#Goal#",
                                        //pieInnerText: "#Percent_Del#",
                                        pieInnerText: function (obj) { return `<span style='color:white'>${obj.Percent_Del}</span>`; },
                                        shadow: 0,
                                        legend: {
                                            values: [{ text: "Manual", color: "#e8591c" }, { text: "Auto", color: "#30b358" }],
                                            valign: "middle",
                                            align: "top",
                                            width: 90,
                                            layout: "x"
                                        },
                                    },
                                ]
                            },
                        ]
                    },
                    {
                        paddingY: 30,
                        height: 500,
                        rows: [
                            {
                                template: "<div style='width:100%;text-align:center;font-size:14px;'>DAILY REPORT</div>",
                                height: 30
                            },
                            {

                                cols: [
                                    {
                                        view: "chart",
                                        id: $n("chart_Bar"),
                                        height: 300,
                                        type: "stackedBar",
                                        barWidth: 30,
                                        radius: 1,
                                        xAxis: {
                                            template: "#Day_Date#",
                                            title: "Date",
                                        },
                                        yAxis: {
                                            start: 0,
                                            step: 20,
                                            end: 100
                                        },
                                        legend: {
                                            values: [{ text: "Manual", color: "#e8591c" }, { text: "Auto", color: "#30b358" }],
                                            valign: "middle",
                                            align: "center",
                                            width: 90,
                                            layout: "x"
                                        },
                                        series: [
                                            {
                                                value: "#Percent_not_null#",
                                                label: `<span style='color:white; font-size:7px;'>#Percent_not_null#%</span>`,
                                                //label: "#Percent_not_null#",
                                                color: "#30b358",
                                                //css: { "font-size": "9px" },
                                                item: {
                                                    borderColor: "#30b358",
                                                    color: "#ffffff"
                                                },
                                                tooltip: {
                                                    template: "Auto : #Percent_not_null#%"
                                                }
                                            },
                                            {
                                                value: "#Percent_null#",
                                                //label: "#Percent_null#",
                                                label: `<span style='color:white; font-size:7px;'>#Percent_null#%</span>`,
                                                color: "#e8591c",
                                                // css: { "font-size": "9px" },
                                                item: {
                                                    borderColor: "#e8591c",
                                                    color: "#ffffff"
                                                },
                                                tooltip: {
                                                    template: "Manual : #Percent_null#%"
                                                }
                                            },
                                        ],
                                        on: {
                                            "onItemClick": function (id, e, trg) {
                                                var obj1 = ele('form1').getValues();
                                                var obj2 = ele("chart_Bar").getItem(id);
                                                var obj = { ...obj1, ...obj2 };
                                                //console.log(obj);
                                                ajax(fd, obj, 3, function (json) {
                                                    var data = json.data;
                                                    //console.log(data);
                                                    ele("truckNo_Date").setValue(data[0].truckNo_Date);

                                                    ele("label_Total").setValue(data[0].label_Total);
                                                    ele("label_Pick_Total").setValue(data[0].label_Pick_Total);
                                                    ele("label_Delivery_Total").setValue(data[0].label_Delivery_Total);

                                                    ele("label_Total_Auto").setValue(data[0].label_Total_Auto);
                                                    ele("label_Pick_Auto").setValue(data[0].label_Pick_Auto);
                                                    ele("label_Delivery_Auto").setValue(data[0].label_Delivery_Auto);

                                                    ele("label_Total_Manual").setValue(data[0].label_Total_Manual);
                                                    ele("label_Pick_Manual").setValue(data[0].label_Pick_Manual);
                                                    ele("label_Delivery_Manual").setValue(data[0].label_Delivery_Manual);
                                                }, null,
                                                    function (json) {
                                                    });

                                            }
                                        },
                                    },
                                    {
                                        //width: 350,
                                        rows: [
                                            {
                                                cols: [
                                                    { view: "label", id: $n("truckNo_Date"), label: "", align: "center", width: 90, css: { "background": "#ffffff" } },
                                                    { view: "label", label: "Total", align: "center", width: 100, css: { "background": "#f4f5f9" } },
                                                    { view: "label", label: "Auto", align: "center", width: 100, css: { "background": "#30b358" } },
                                                    { view: "label", label: "Manual", align: "center", width: 100, css: { "background": "#e8591c" } },
                                                ]
                                            },
                                            {
                                                cols: [
                                                    { view: "label", label: "Total", align: "center", width: 90, css: { "background": "#f4f5f9" } },
                                                    { view: "label", id: $n("label_Total"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Total_Auto"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Total_Manual"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                ]
                                            },
                                            {
                                                cols: [
                                                    { view: "label", label: "Pick Up", align: "center", width: 90, css: { "background": "#f4f5f9" } },
                                                    { view: "label", id: $n("label_Pick_Total"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Pick_Auto"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Pick_Manual"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                ]
                                            },
                                            {
                                                cols: [
                                                    { view: "label", label: "Delivery", align: "center", width: 90, css: { "background": "#f4f5f9" } },
                                                    { view: "label", id: $n("label_Delivery_Total"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Delivery_Auto"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                    { view: "label", id: $n("label_Delivery_Manual"), label: "0", width: 100, align: "center", css: { "background": "#ffffff" } },
                                                ]
                                            },
                                        ]
                                    }

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