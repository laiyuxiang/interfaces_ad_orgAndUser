<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2018/01/08
 * Time: 10:10
 */
namespace Interfaces\Controller;

use Unis\UnisSoap;

class OrgGDController extends UnisSoap
{
    private $org;

    public function __construct()
    {
        parent::__construct();
        $this->org = $this->getModel('OrgGD');
    }

    public function index()
    {
//        $a = $this->getPathInfo('38e740ab7b46f54c99b5eff70a455f52');
//        print_r($a);die;
//        $usersInfo = $this->org->getAdUsers();
//        print_r($usersInfo);die;
        set_time_limit(0);
        ini_set('memory_limit', -1);
        header('content-type: text/html;charset=utf-8');

        // 组织单位同步start
        $orgsInfo = $this->org->getAdOrgs();

        //   print_r($orgsInfo);die;
        if($orgsInfo)
        {
            $orgMidData = array();
            foreach($orgsInfo as $k=>$orgInfo)
            {
                // guid乱码转换为十六进制
                $orgInfo['objectguid'][0] = bin2hex($orgInfo['objectguid'][0]);
                if($orgInfo['name'][0] == '集团用户')
                {
                    continue;
                }
                $tmp = array();
                $tmp['objectguid'] = $orgInfo['objectguid'][0];

                $tmp['name'] = $orgInfo['name'][0];
                if($tmp['name'] == 'scvmm')
                {
                    continue;
                }
                $tmp['shortname'] = $orgInfo['name'][0];
                $tmp['canonicalName'] = $orgInfo['objectguid'][0];// 存放id
                $tmp['path'] = $orgInfo['objectguid'][0]; // 存放id

                $pathInfo = explode(',', $orgInfo['dn']);
                foreach($pathInfo as $pathK => $pathItem)
                {
                    $pathInfo[$pathK] = explode('=', $pathItem)[1];
                }
                $pathItemNum = count($pathInfo)-2;
                $tmp['level'] = 1;
                $tmp['status'] = 1;

                if($pathItemNum == 2 || $pathItemNum == 1)
                {
                    $tmp['fatherid'] = 0;
                }else
                {
                    $tmp['fatherid'] = $pathInfo[1]; // 除顶级单位外，其余存放父部门名称
                }

                $tmp['type'] = 1;
                $tmp['comid'] = 1;
                $tmp['rid'] = $tmp['objectguid'];



                $orgMidData[] = $tmp;
            }

            $this->orgTongBu($orgMidData, C('ORGANIZATION'));
        }

        // 组织单位同步end

        // 组织用户同步stprintart
        $usersInfo = $this->org->getAdUsers();

        if($usersInfo)
        {
            $userMidData = array();
            foreach($usersInfo as $kk=>$userInfo)
            {
                /*echo '<pre>';
                var_dump($userInfo);
                echo '</pre>';*/
                $tmp = array();
                $tmp['samaccountname'] = $userInfo['samaccountname'][0];
                $tmp['displayName'] = $userInfo['displayname'][0];
                $pathInfo = explode(',', $userInfo['dn']);
                foreach($pathInfo as $pathK => $pathItem)
                {
                    $pathInfo[$pathK] = explode('=', $pathItem)[1];
                }
                $tmp['department'] = $pathInfo[1];
                $tmp['sex'] = 1;
                $tmp['age'] = 1;


                $deptInfo = $this->org->selectArchData('organization', 'name=\'' . $tmp['department'] . '\'');

                if($deptInfo)
                {
                    $tmp['deptid'] = $deptInfo[0]['id'];
                }else
                {
                    $tmp['deptid'] = -1;
                }

                $tmp['groupdeptid'] = $tmp['deptid'];

                $tmp['status'] = 1;
                $userControl = $userInfo['useraccountcontrol'][0];
                if ($userControl >= 16777216)            //TRUSTED_TO_AUTH_FOR_DELEGATION - 允许该帐户进行委派
                {
                    $userControl = $userControl - 16777216;
                }
                if ($userControl >= 8388608)            //PASSWORD_EXPIRED - (Windows 2000/Windows Server 2003) 用户的密码已过期
                {
                    $userControl = $userControl - 8388608;
                }
                if ($userControl >= 4194304)            //DONT_REQ_PREAUTH
                {
                    $userControl = $userControl - 4194304;
                }
                if ($userControl >= 2097152)            //USE_DES_KEY_ONLY - (Windows 2000/Windows Server 2003) 将此用户限制为仅使用数据加密标准 (DES) 加密类型的密钥
                {
                    $userControl = $userControl - 2097152;
                }
                if ($userControl >= 1048576)            //NOT_DELEGATED - 设置此标志后，即使将服务帐户设置为信任其进行 Kerberos 委派，也不会将用户的安全上下文委派给该服务
                {
                    $userControl = $userControl - 1048576;
                }
                if ($userControl >= 524288)            //TRUSTED_FOR_DELEGATION - 设置此标志后，将信任运行服务的服务帐户（用户或计算机帐户）进行 Kerberos 委派。任何此类服务都可模拟请求该服务的客户端。若要允许服务进行 Kerberos 委派，必须在服务帐户的 userAccountControl 属性上设置此标志
                {
                    $userControl = $userControl - 524288;
                }
                if ($userControl >= 262144)            //SMARTCARD_REQUIRED - 设置此标志后，将强制用户使用智能卡登录
                {
                    $userControl = $userControl - 262144;
                }
                if ($userControl >= 131072)            //MNS_LOGON_ACCOUNT - 这是 MNS 登录帐户
                {
                    $userControl = $userControl - 131072;
                }
                if ($userControl >= 65536)            //DONT_EXPIRE_PASSWORD-密码永不过期
                {
                    $userControl = $userControl - 65536;
                }
                if ($userControl >= 2097152)            //MNS_LOGON_ACCOUNT - 这是 MNS 登录帐户
                {
                    $userControl = $userControl - 2097152;
                }
                if ($userControl >= 8192)            //SERVER_TRUST_ACCOUNT - 这是属于该域的域控制器的计算机帐户
                {
                    $userControl = $userControl - 8192;
                }
                if ($userControl >= 4096)            //WORKSTATION_TRUST_ACCOUNT - 这是运行 Microsoft Windows NT 4.0 Workstation、Microsoft Windows NT 4.0 Server、Microsoft Windows 2000 Professional 或 Windows 2000 Server 并且属于该域的计算机的计算机帐户
                {
                    $userControl = $userControl - 4096;
                }
                if ($userControl >= 2048)            //INTERDOMAIN_TRUST_ACCOUNT - 对于信任其他域的系统域，此属性允许信任该系统域的帐户
                {
                    $userControl = $userControl - 2048;
                }
                if ($userControl >= 512)            //NORMAL_ACCOUNT - 这是表示典型用户的默认帐户类型
                {
                    $userControl = $userControl - 512;
                }
                if ($userControl >= 256)            //TEMP_DUPLICATE_ACCOUNT - 此帐户属于其主帐户位于另一个域中的用户。此帐户为用户提供访问该域的权限，但不提供访问信任该域的任何域的权限。有时将这种帐户称为“本地用户帐户”
                {
                    $userControl = $userControl - 256;
                }
                if ($userControl >= 128)            //ENCRYPTED_TEXT_PASSWORD_ALLOWED - 用户可以发送加密的密码
                {
                    $userControl = $userControl - 128;
                }
                if ($userControl >= 64)            //PASSWD_CANT_CHANGE - 用户不能更改密码。可以读取此标志，但不能直接设置它
                {
                    $userControl = $userControl - 64;
                }
                if ($userControl >= 32)            //PASSWD_NOTREQD - 不需要密码
                {
                    $userControl = $userControl - 32;
                }
                if ($userControl >= 16)            //LOCKOUT
                {
                    $userControl = $userControl - 16;
                }
                if ($userControl >= 8)            //HOMEDIR_REQUIRED - 需要主文件夹
                {
                    $userControl = $userControl - 8;
                }
                if ($userControl >= 2)
                {
                    $tmp['status'] = 1;
                }

                $tmp['password'] = md5('1qaz2wsx');
                $tmp['beginDate'] = date('Y-m-d H:i:s', time());
                $tmp['endDate'] = date('Y-m-d H:i:s', time() + 5*365*24*3600);
                $tmp['changePwdTime'] = $tmp['beginDate'];
                $tmp['creater'] = 'OA';
                $tmp['groupdeptname'] = $tmp['department'];

                $pathInfo = $this->getPathInfo($tmp['deptid']);
                $tmp['fulldeptname'] = $pathInfo['pathname'];
                $tmp['fulldeptid'] = $pathInfo['path'];
                $tmp['comid'] = 1;
                $tmp['rid'] = $userInfo['samaccountname'][0];
                $tmp['position'] = $userInfo['title'][0];
                $tmp['email'] = $userInfo['userprincipalname'][0];
                $tmp['workTel'] = $userInfo['telephonenumber'][0];
                $tmp['phone'] = $userInfo['mobile'][0];
                $userMidData[] = $tmp;



            }

            $this->userTongbu($userMidData, C('USER'), 1);
        }
        // 组织用户同步end

        $this->org->unbindLink();


    }


    /**
     * 组织单位全量同步，增量同步都使用该方法实现
     * @param $orgMidData 转换完成的中间库组织单位数据，二维数组
     * @param $orgConfig 中间库字段和档案库组织表字段的映射，一维数组
     *
     */
    public function orgTongBu($orgMidData, $orgConfig)
    {
        foreach($orgMidData as $v)
        {
            $orgArchData = array();
            foreach($v as $itemk => $itemv)
            {
                // 过滤掉为配置的转换字段
                if(isset($orgConfig[$itemk]))
                {
                    $orgArchData[$orgConfig[$itemk]] = $itemv;
                }
            }

            //数据查重
            $isExist = $this->org->selectArchData('organization', 'rid=\'' . $orgArchData['rid'] . '\'');
            if($isExist)
            {
                // 存在则更新
                $rid = $orgArchData['rid'];
                unset($orgArchData['rid']);
                $this->org->updateArchData('organization', $orgArchData, 'rid=\'' . $rid . '\'');
            }else
            {
                // 不存在则添加
                $this->org->insertArchData('organization', $orgArchData);
            }
        }

        // 将fatherid更新为对应部门的id
        $updateFatherid = "update organization as o1 set o1.fatherid=(select o3id from (select o2.id as o3id, o2.name as o3name from organization as o2 ) as o3 where o3name=o1.fatherid limit 1) where o1.fatherid != '0' and o1.rid is not null";
        $this->org->execute($updateFatherid);
        // 更新path和pathname
        $needUpdateOrgs = $this->org->selectArchData('organization', 'path=rid');
        if($needUpdateOrgs)
        {
            foreach($needUpdateOrgs as $v)
            {
                $pathInfo = $this->getPathInfo($v['id']);
                $pathInfo['path'] = ('/' . $pathInfo['path'] . '/');
                $pathInfo['pathname'] = ('/' . $pathInfo['pathname'] . '/');
                $this->org->updateArchData('organization', $pathInfo, 'rid=\'' . $v['rid'] . '\'');
            }
        }
    }


    /**
     * 组织用户全量同步，增量同步都使用该方法。全量同步先失效所有用户，然后根据具体信息
     * @param $userMidData 格式处理完成的组织用户中间库信息，二维数组
     * @param $userConfig 组织用户中间库字段和档案库字段的对应关系，一维数组
     * @param $tongbuFlg 同步标志，1全量同步，2增量同步
     */
    public function  userTongbu($userMidData, $userConfig, $tongbuFlg)
    {
        // 增量同步：增量同步除了第一次同步，之后每次同步获取到的都是发生变更的用户数据，只需根据变更内容，进行增加、删除、更新
        // 全量同步：全量同步每次都是获取到全部的用户数据，但是不包括已经被删除的。针对已经获取到的用户数据，直接执行增加、更新即可。对于被删除的用户数据，应该如何处理？全量同步开始之前，所有用户设置为失效，即status为0，但是内置的用户需要排除，如admin管理员，代码预定所有包含admin的账号全部跳过。然后，只对获取到的用户数据，进行具体的是否有效的设置，其余用户全部无效处理
        if($tongbuFlg == 1)
        {
            // 所有登录名不含admin的账号全部设置为失效
            $this->org->updateArchData('s_user', ['status'=>0], 'rid not regexp \'[aA][dD][mM][iI][nN]\' and deptname != \'测试用户\'');
        }

        foreach($userMidData as $v)
        {
            $userArchData = array();
            foreach($v as $itemk => $itemv)
            {
                // 过滤掉为配置的转换字段
                if(isset($userConfig[$itemk]))
                {
                    $userArchData[$userConfig[$itemk]] = $itemv;
                }
            }

            // 判断用户是否属于档案系统
            $isArchiver = $this->org->selectArchData('organization', 'name=\'' . $userArchData['deptname'] . '\'');
            if(!$isArchiver) continue;

            //数据查重
            $isExist = $this->org->selectArchData('s_user', 'rid=\'' . $userArchData['rid'] . '\'');
            if($isExist)
            {
                // 存在则更新
                $rid = $userArchData['rid'];
                unset($userArchData['rid']);
                unset($userArchData['password']);
                unset($userArchData['changePwdTime']);
                //echo '执行更新', '<br>';
                $this->org->updateArchData('s_user', $userArchData, 'rid=\'' . $rid . '\'');
            }else
            {
                // 不存在则添加
                //echo '执行添加', '<br>';
                $this->org->insertArchData('s_user', $userArchData);
            }
        }
    }


    /**
     * 根据组织id，获取对应的父路径和父路径id
     * @param $deptId string 组织id
     * @return array
     */
    public function getPathInfo($deptId)
    {
        $res = array();
        $res['path'] = '';
        $res['pathname'] = '';

        $deptInfo = $this->org->selectArchData('organization', 'id=\'' . $deptId . '\'');
        if(!empty($deptInfo) && $deptInfo[0]['fatherid'] != '0')
        {
            $tmp = $this->getPathInfo($deptInfo[0]['fatherid']);
            $res['path'] .= ( $tmp['path'] . '/' . $deptInfo[0]['id']);
            $res['pathname'] .= ($tmp['pathname'] . '/' . $deptInfo[0]['name']);
        }else
        {
            $res['path'] .= $deptId;
            $res['pathname'] .= $deptInfo[0]['name'];
        }

        return $res;
    }
}