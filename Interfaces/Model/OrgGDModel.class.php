<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/01/08
 * Time: 10:10
 */
namespace Interfaces\Model;

use Unis\UnisModel;

class OrgGDModel extends UnisModel
{
    private $midLink;
    private $tarLink;

    public function __construct()
    {


        $this->midLink = ldap_connect(C('HOST'), C('PORT')) or die('连接ldap服务器失败');

        ldap_set_option($this->midLink, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->midLink, LDAP_OPT_REFERRALS, 0);
        $flg = ldap_bind($this->midLink, C('RDN'), C('PWD')) or die('登录ldap服务器失败');
        $this->tarLink = $this->getProxy('db');
    }


    /**
     * 获取AD域数据
     * @param $type string 数据类别，organization组织单位数据，user用户数据
     * @return array
     */
    public function getAdOrgs()
    {
        $bdn = 'OU=集团用户,DC=sinomach,DC=com';
        $filter = '(objectClass=OrganizationalUnit)';
		$res = null;
        $res = ldap_search($this->midLink, $bdn, $filter);

		$error = ldap_error($this->midLink);
        $res = ldap_get_entries($this->midLink, $res);

        if($res['count'] > 0)
        {
            unset($res['count']);
        }else
        {
            $res = 0;
        }
		//$res = eval('return ' . var_export($res) . ';');
        return $res;
    }
	
	public function getAdUsers()
    {
        $bdn =  'OU=集团用户,DC=sinomach,DC=com';
		$filter = '(&(objectClass=user)(objectCategory=person))';
        $res = ldap_search($this->midLink, $bdn, $filter);
		$error = ldap_error($this->midLink);
        $res = ldap_get_entries($this->midLink, $res);
        if($res['count'] > 0)
        {
            unset($res['count']);
        }else
        {
            $res = 0;
        }
		//$res = eval('return ' . var_export($res) . ';');
        return $res;
    }

    /**
     * 断开AD域连接
     */
    public function unbindLink()
    {
        @ldap_unbind($this->midLink);
    }




    /**通用代码start**/
    /**
     * 档案库增加数据
     */
    public function insertArchData($table, $data)
    {
        $res = $this->tarLink->insert($data, ['table'=>$table]);
        return $res;
    }

    /**
     * 档案库更新数据
     */
    public function updateArchData($table, $data, $where)
    {
        $res = $this->tarLink->where($where)->update($data, ['table'=>$table]);
        return $res;
    }

    /**
     * 档案库数据检索
     */
    public function selectArchData($table, $where)
    {
        $res = $this->tarLink->where($where)->select(['table'=>$table]);
        return $res;
    }
    /**通用代码end**/

    public function execute($sql)
    {
        $this->tarLink->execute($sql);
    }
}