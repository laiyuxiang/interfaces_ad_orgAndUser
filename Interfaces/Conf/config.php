<?php
return array(
	//'配置项'=>'配置值'
    'HOST' => 'ldap://10.1.1.1', //ip
    'PORT' => '389', //默认端口389
    'RDN' => '',  //账号
    'PWD' => '', //密码

    // 组织单位配置信息
    'ORGANIZATION' => array(
        // 中间库字段 => 档案库字段
        'objectguid'    => 'id',
        'name'          => 'name',

        'canonicalName' => 'pathname',
        'path'      => 'path',
        'level'     => 'level',
        'status'    => 'status',
        'fatherid'  => 'fatherid',
        'type'      => 'type',
        'comid'     => 'comid',
        'shortname' => 'shortname',
        'rid'       => 'rid'
    ),

    'USER' => array(
        // 中间库字段 => 档案库字段
        'samaccountname'            => 'id',
        'displayName'   => 'name',
        'department'    => 'deptname',

        'sex'           => 'sex',
        'age'           => 'age',
        'deptid'        => 'deptid',
        'groupdeptid'   => 'groupdeptid',
        'status'        => 'status',
        'password'      => 'password',
        'beginDate'     => 'beginDate',
        'endDate'       => 'endDate',
        'changePwdTime' => 'changePwdTime',
        'creater'       => 'creater',
        'groupdeptname' => 'groupdeptname',
        'fulldeptname'  => 'fulldeptname',
        'fulldeptid'    => 'fulldeptid',
        'comid'         => 'comid',
        'rid'           => 'rid',
		'email' => 'email',
		'phone' => 'phone',
		'workTel' => 'workTel',
		'position' => 'position'
    )
);