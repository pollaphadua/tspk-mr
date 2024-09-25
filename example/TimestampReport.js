var header_TimestampReport = function()
{
	var menuName="TimestampReport_",fd = "Report/"+menuName+"data.php";

    var daily_report_ctx=null,
    daily_report_pieChart_ctx1=null,
    daily_report_pieChart_ctx2=null,
    daily_report_pieChart_ctx3=null,

    daily_report_pieChart1=null,
    daily_report_pieChart2=null,
    daily_report_pieChart3=null,
    daily_report=null
    ;
    function init()
    {
        daily_report_ctx  = document.getElementById($n('daily_report_chart')).getContext('2d');
        daily_report_pieChart_ctx1  = document.getElementById($n('daily_report_pieChart1')).getContext('2d');
        daily_report_pieChart_ctx2  = document.getElementById($n('daily_report_pieChart2')).getContext('2d');
        daily_report_pieChart_ctx3  = document.getElementById($n('daily_report_pieChart3')).getContext('2d');
        Chart.register(ChartDataLabels);

    };

    function ele(name)
    {
        return $$($n(name));
    };

    function $n(name)
    {
        return menuName+name;
    };
    
    function focus(name)
    {
        setTimeout(function(){ele(name).focus();},100);
    };
    
    function setView(target,obj)
    {
        var key = Object.keys(obj);
        for(var i=0,len=key.length;i<len;i++)
        {
            target[key[i]] = obj[key[i]];
        }
        return target;
    };

    function vw1(view,name,label,obj)
    {
        var v = {view:view,required:true,label:label,id:$n(name),name:name,labelPosition:"top"};
        return setView(v,obj);
    };

    function vw2(view,id,name,label,obj)
    {
        var v = {view:view,required:true,label:label,id:$n(id),name:name,labelPosition:"top"};
        return setView(v,obj);
    };

	return {
        view: "scrollview",
        scroll: "native-y",
        id:"header_TimestampReport",
        body: 
        {
        	id:"TimestampReport_id",
        	type:"clean",
    		rows:
    		[
    		    {
                    view: "form", scroll: false, id: $n('form1'),
                    elements:
                    [
                        {
                            cols:
                            [
                                vw1("datepicker", 'start', "Start (วันเริ่มต้น)", {type:"date",value:dayjs().format("YYYY-MM-01"),stringResult:true,...datatableDateFormat}),
                                vw1("datepicker", 'stop', "Stop (วันสิ้นสุด)", {type:"date",value:dayjs().endOf('month').format("YYYY-MM-DD"),stringResult:true,...datatableDateFormat}),
                                vw1("combo", 'gps', "GPS", {options:['OK','ALL'],value:'OK'}),
                                {
                                    rows:
                                    [
                                        {},
                                        vw1('button', 'find', 'Find (ค้นหา)', {
                                            width: 200,
                                            on:
                                            {
                                                onItemClick: function () 
                                                {
                                                    var obj = ele('form1').getValues();
                                                    var btn = this;
                                                    ajax(fd,obj,1,function(json)
                                                    {
                                                        let labels = [... new Set(json.data.map(data => data.Day))];
                                                        let groupByCategory = json.data.reduce((group, data) => {
                                                            const { type } = data;
                                                            group[type] = group[type] ?? [];
                                                            group[type].push(data);
                                                            return group;
                                                        }, {});                                                                                                        
                                                        
                                                        let = UserKeyPercentageTotal = new Set([... groupByCategory['Total'].map(data => data.TypeUserKeyPercentage)]);
                                                        let = SystemKeyPercentageTotal = new Set([... groupByCategory['Total'].map(data => data.TypeSystemKeyPercentage)]);

                                                        let = UserKeyPercentagePick = new Set([... groupByCategory['Pick'].map(data => data.TypeUserKeyPercentage)]);
                                                        let = SystemKeyPercentagePick = new Set([... groupByCategory['Pick'].map(data => data.TypeSystemKeyPercentage)]);

                                                        let = UserKeyPercentageDrop = new Set([... groupByCategory['Drop'].map(data => data.TypeUserKeyPercentage)]);
                                                        let = SystemKeyPercentageDrop = new Set([... groupByCategory['Drop'].map(data => data.TypeSystemKeyPercentage)]);

                                                        let dataSetPie1 = [... new Set(SystemKeyPercentageTotal),... new Set(UserKeyPercentageTotal)];
                                                        let dataSetPie2 = [... new Set(SystemKeyPercentagePick),... new Set(UserKeyPercentagePick)];
                                                        let dataSetPie3 = [... new Set(SystemKeyPercentageDrop),... new Set(UserKeyPercentageDrop)];
                                                        
                                                        let dataPie = {
                                                            labels: ['Auto', 'Manual'],
                                                            datasets:[]
                                                        };

                                                        let configPie = 
                                                        {
                                                            type: 'pie',
                                                            options: {
                                                                responsive: true,
                                                                plugins: {
                                                                    legend: {
                                                                        position: 'top',
                                                                    },
                                                                    title: {
                                                                        display: true,
                                                                        text: ''
                                                                    },       
                                                                    datalabels:{}                                                             
                                                                }
                                                            },
                                                        };
                                                        
                                                        let label =  {
                                                            color: 'white',
                                                            display: function(context) {
                                                                return context.dataset.data[context.dataIndex] > 0;
                                                            },
                                                            font: {
                                                                weight: 'bold'
                                                            },
                                                            formatter: function(value, context) {
                                                                return value + ' %';
                                                            },
                                                        };

                                                        let configPie1 = JSON.parse(JSON.stringify(configPie));
                                                        configPie1.options.plugins.datalabels = label;
                                                        let dataPie1 = JSON.parse(JSON.stringify(dataPie));
                                                        configPie1.options.plugins.title.text = 'Total';
                                                        dataPie1.datasets = [
                                                            {
                                                                label: '',
                                                                data: dataSetPie1,
                                                                backgroundColor:['#379F7A','#F77825']
                                                            }
                                                        ]
                                                        configPie1.data = dataPie1;
                                                        if(daily_report_pieChart1)
                                                        {
                                                            daily_report_pieChart1.destroy();
                                                            daily_report_pieChart1 = null;
                                                        }
                                                        daily_report_pieChart1 = new Chart(daily_report_pieChart_ctx1,configPie1);

                                                        let configPie2 = JSON.parse(JSON.stringify(configPie));
                                                        configPie2.options.plugins.datalabels = label;
                                                        let dataPie2 = JSON.parse(JSON.stringify(dataPie));
                                                        configPie2.options.plugins.title.text = 'Pick';
                                                        dataPie2.datasets = [
                                                            {
                                                                label: '',
                                                                data: dataSetPie2,
                                                                backgroundColor:['#379F7A','#F77825']
                                                            }
                                                        ]
                                                        configPie2.data = dataPie2;
                                                        if(daily_report_pieChart2)
                                                        {
                                                            daily_report_pieChart2.destroy();
                                                            daily_report_pieChart2 = null;
                                                        }
                                                        daily_report_pieChart2 = new Chart(daily_report_pieChart_ctx2,configPie2);
                                                        

                                                        let configPie3 = JSON.parse(JSON.stringify(configPie));
                                                        configPie3.options.plugins.datalabels = label;
                                                        let dataPie3 = JSON.parse(JSON.stringify(dataPie));
                                                        configPie3.options.plugins.title.text = 'Drop';
                                                        dataPie3.datasets = [
                                                            {
                                                                label: '',
                                                                data: dataSetPie3,
                                                                backgroundColor:['#379F7A','#F77825']
                                                            }
                                                        ]
                                                        configPie3.data = dataPie3;

                                                        if(daily_report_pieChart3)
                                                        {
                                                            daily_report_pieChart3.destroy();
                                                            daily_report_pieChart3 = null;
                                                        }
                                                        daily_report_pieChart3 = new Chart(daily_report_pieChart_ctx3,configPie3);

                                                        let = UserKeyPercentageDaily = [... groupByCategory['Total'].map(data => data.UserKeyPercentage)];
                                                        let = SystemKeyPercentageDaily = [... groupByCategory['Total'].map(data => data.SystemKeyPercentage)];
                                                        
                                                        
                                                        const dataBar = {
                                                        labels: labels,
                                                        datasets: [
                                                            {
                                                                label: 'Auto',
                                                                data: SystemKeyPercentageDaily,
                                                                backgroundColor:'#379F7A'
                                                            },
                                                            {
                                                                label: 'Manual',
                                                                data: UserKeyPercentageDaily,                                                            
                                                                backgroundColor:'#F77825'
                                                            },
                                                        ]
                                                        };                                                              

                                                        const configBar = {
                                                            type: 'bar',
                                                            data: dataBar,
                                                            options: {
                                                                plugins: {
                                                                    title: {
                                                                        display: false,
                                                                        text: 'Chart.js Bar Chart - Stacked'
                                                                    },
                                                                    datalabels: {
                                                                        color: 'white',
                                                                        display: function(context) {
                                                                        return context.dataset.data[context.dataIndex] > 0;
                                                                        },
                                                                        font: {
                                                                        weight: 'bold'
                                                                        },
                                                                    }
                                                                },
                                                                responsive: true,
                                                                scales: {
                                                                x: {
                                                                    stacked: true,
                                                                },
                                                                y: {
                                                                    stacked: true
                                                                }
                                                                }
                                                            }
                                                        };

                                                        if(daily_report)
                                                        {
                                                            daily_report.destroy();
                                                            daily_report = null;
                                                        }
                                                        daily_report = new Chart(daily_report_ctx,configBar);  

                                                    },btn,
                                                    function(json)
                                                    {
                                                    });                                                    
                                                    
                                                }
                                            },
                                        }),
                                    ]
                                },
                                {},{}
                            ]
                        },
                        {
                            cols:
                            [
                                {
                                    template:'<canvas height="350" id="'+$n('daily_report_pieChart1')+'"></canvas>',autoheight:true,
                                },
                                {
                                    template:'<canvas height="350" id="'+$n('daily_report_pieChart2')+'"></canvas>',autoheight:true,
                                },
                                {
                                    template:'<canvas height="350" id="'+$n('daily_report_pieChart3')+'"></canvas>',autoheight:true,
                                },
                            ]
                        },
                        {
                            template:'<canvas height="350" width="900" id="'+$n('daily_report_chart')+'" style=""></canvas>',autoheight:true,
                        },
                    ]
                }
            ],on:
            {
                onHide:function()
                {
                    
                },
                onShow:function()
                {

                },
                onAddView:function()
                {
                	init();
                }
            }
        }
    };
};