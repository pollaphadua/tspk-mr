<?php

    function checkVendorCode($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT * from tbl_vendor_master t1 where t1.Vendor_Code='$data' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }

    function extracCodeVendor($Vendor)
    {
        $VendorAr = explode(' | ',$Vendor);
		if(count($VendorAr) !=2) closeDBT($mysqli,2,'Vendor รูปแบบการกรอกข้อมูลไม่ถูกต้อง');
		return $VendorAr;
    }

    function checkItemCode($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT * from tbl_packages t1 where t1.package_name_en='$data' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }
	

    function extracCodeItem($Item)
    {
        $ItemAr = explode(' | ',$Item);
		if(count($ItemAr) !=2) closeDBT($mysqli,2,'Item รูปแบบการกรอกข้อมูลไม่ถูกต้อง');
		return $ItemAr;
    }

    function checkPartNumber($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT * from tbl_partmaster t1 where t1.part_number='$data' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }

    function extracPartNumber($data)
    {
        $dataAr = explode(' | ',$data);
		if(count($dataAr) !=2) closeDBT($mysqli,2,'Part Number รูปแบบการกรอกข้อมูลไม่ถูกต้อง');
		return $dataAr;
    }

    function checkWareHouseCode($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT * from tbl_warehouse_master t1 where t1.Warehouse_Code='$data' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }

    function extracCodeCustomer($Item)
    {
        // 1320X1140X48_4(AA23DCB1011R) | CARTON BOX
        $ItemAr = explode(' | ',$Item);
        if(count($ItemAr) !=2) closeDBT($mysqli,2,'Item รูปแบบการกรอกข้อมูลไม่ถูกต้อง');
        $ItemAr = explode('(',$ItemAr[0]);
        if(count($ItemAr) !=2) closeDBT($mysqli,2,'Item รูปแบบการกรอกข้อมูลไม่ถูกต้อง');
		return $ItemAr;
    }

    function checkCustomerCode($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT * from tbl_customer_items t1 where t1.Cus_Code='$data' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }

    function getCustomerCodeAndItemCode($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT concat(t1.Cus_Code,'(',t2.Item_Code,')',' | ',t2.Item_Name) value
	    from tbl_customer_items t1
        left join tbl_items_master t2 on t1.Item_ID=t2.Item_ID
        left join tbl_customer_master t3 on t1.Customer_ID=t3.Customer_ID
        where (t1.Cus_Code ='$data[Cus_Code]') limit 1";
        return sqlError($mysqli,$lineCode,$sql);

        // and t3.Customer_Code='$Customer_Code'
    }

    function getVendorCodeAndName($mysqli,$lineCode,$data,$rollback=1)
    {
        $sql = "SELECT concat(t1.Vendor_Code,' | ',t1.Vendor_Name) value
        from tbl_vendor_master t1 where t1.Vendor_Code='$data[Vendor_Code]' limit 1";
        return sqlError($mysqli,$lineCode,$sql);
    }

    function systemToHumanDate($date)
    {
        $dateAr = explode('-',$date);
        return $dateAr[2].'/'.$dateAr[1].'/'.substr($dateAr[0],2,2);
    }

    function systemDateConvert($date)
    {
        $dateAr = explode('/',$date);
        $y = strlen($dateAr[2]) == 2 ? '20'.$dateAr[2]:$dateAr[2];
        $m = strlen($dateAr[1]) == 1 ? '0'.$dateAr[1]:$dateAr[1];
        $d = strlen($dateAr[0]) == 1 ? '0'.$dateAr[0]:$dateAr[0];
        return $y.'-'.$m.'-'.$d;
    }

?>