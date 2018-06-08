<?php
namespace Interfaces\Controller;

use Unis\UnisController;

class  SsoController extends BaseController
{
    protected $loginModel;
    /**
     * @return 默认声明的结构类，继承并改写父方法，必须执行父方法
     */

    public function __construct()
    {
        parent::__construct();
        $this->loginModel=$this->getModel('Login','Login');
    }

    public function check(){
        echo 1;die;
        $requestUrl = "10.1.9.70/Interfaces/Sso/vduanyan";//断言返回的地址
        $userSys = session('userID');//session中的userid以此判断是否登录
        print_r($userSys);
        if(empty($userSys)){//若为空进行下面的单点操作
            redirect("http://10.1.1.203/sso/SSO?providerId=10&TARGET=".$requestUrl); //断言
        }else{
            redirect("http://localhost"); //断言
        }
    }
    public function vduanyan(){
        //验证断言
        $url = "http://localhost:8080/sso/services/rest/token/verifySAMLResponse";
    }

    public function index()
    {
        $username = $_POST['userid'];
        session.session_destroy();
        session.session_start();

        if(isset($username) && $username != null)
        {
            // 验证用户是否被锁定
            $this->checkLock($username);
            // 获取用户所属部门
            $sql = 'select deptid from s_user where id=\'' . $username . '\'';
            $res = $this->getProxy('db')->query($sql);
            if($res)
            {
                $deptid = $res[0]['deptid'];
            }else
            {
                redirect(getUrl('Login/Login/index'));
            }

            $result = $this->getProxy('User')->ssoLogin($username, $deptid);
            if($result ['status'] == 'fail')
            {
                redirect(getUrl('Login/Login/index'));
            }else
            {
                $result = $result ['result'];
                $user = $result ['user'];
                $userObj = (object)$user;
                clearCache( $username . 'ErrorPassHideCode' );
                $partInfo = getCache('partPerson');
                if(empty($partInfo))
                {
                    $this->getProxy('User')->setPartPersonCache();
                }

                $userinfo = $this->getProxy('User')->getUserInfo($username,$user['deptid']);
                $userrole=$userinfo['role'];

                $this->isValidUser ( $userObj );
                $this->checkTime ( $userObj );
                $this->checkInvalidTime ( $userObj,$userrole );

                // 换成单位后，此处设置用户的默认全宗
                $unisSet = new \Unis\UnisSet ();
                $fondsInfo = $unisSet->getDefaultFondsDetail( $user ['comid'] );
                $comInfo =$unisSet->getCompanyInfo ( $user ['comid'] );
                //判断公司是否存在子公司
                $fondModel = D('Setting/Fonds');
                $childComInfo = $fondModel->getChildCompany($user['comid']);
                if( empty($childComInfo) )
                {
                    session('haschildcompany', '0');
                }else
                {
                    session('haschildcompany','1');
                }
                $user ['fondsid'] = $fondsInfo[0]['id'];
                $userObj->fondsid = $user ['fondsid'] ;
                $fulldeptid = $user['fulldeptid'];
                $fulldeptidArr = explode('/', trim($fulldeptid, '/'));
                $groupdepid = $fulldeptidArr[0];
                $orgModel = D('System/Org');
                $orgInfo = $orgModel->getOrgInfoById( $groupdepid );
                $userObj->groupdeptShortName = $orgInfo['shortName'];
                if (!empty(session('mineloginmemo')))
                {
                    unset($_SESSION['mineloginmemo']);
                }

                session ( 'partstatus', 'main' );
                session ( '[regenerate]','');
                session ( 'userInfo', $userObj ); //用户信息
                session ( 'userOrgInfo', ( object ) $userinfo ['organization'] ); //用户所属组织的信息
                session ( 'userRoleInfo', ( object ) $userinfo ['role'] ); //用户所属角色的信息
                session ( 'rolename', $userinfo ['role'] ['name'] );

                //首页布局配置，普通用户(无角色)默认值为  A
                $userinfo['role']['layout'] = empty($userinfo['role']) ? 'A' : $userinfo['role']['layout'];
                if ($user ['id'] === 'admin' )
                {
                    session ( 'homePageLayout', 'BCD' );
                }else
                {
                    session ( 'homePageLayout', $userinfo['role']['layout'] );
                }

                session('isadmin', '0');//非admin用户
                if (! strpos (  $user ['id'] , "admin" ) === false ||  $user ['id']  === 'admin' || ! strpos (  $user ['id'] , "_admin" ) === false)
                {
                    session('isadmin', '1');//admin用户
                }

                session ( 'userID', $user ['id'] );
                session ( 'username', $user ['name'] );
                session ( 'fondsid', $user ['fondsid'] );
                session ( 'comid', $user ['comid'] );
                session ( 'comname', $comInfo[0]['name']);
                session ( 'comcode', $comInfo[0]['code']);
                session ( 'fondscode', $fondsInfo [0] ['code'] );
                session ( 'fondsname', $fondsInfo [0] ['name'] );

                $this->clearProtect ( $user ['id'] );

                redirect ( getUrl ( 'Login/Login/index' ) );
            }
        }
    }


    public function checkLock($username)
    {
        $result = $this->getProxy('System')->validLock($username);
        if (!$result ['status'])
        {
            redirect(getUrl('Login/Login/index'));
        }
    }

    /**
     * 验证用户是否是有效用户
     *
     * @param class $user
     */
    public function isValidUser($user)
    {
        if ($user->status == 0)
        {
            redirect(getUrl('Login/Login/index'));
        }
    }

    /**
     * 验证用户账号日期是否处于有效日期内
     *
     * @param class $user
     */
    public function checkTime($user)
    {
        $begin = strtotime($user->beginDate);
        $end = strtotime($user->endDate);
        $now = time();
        if ($end)
        {
            if ($now > $end || $now < $begin)
            {
                redirect(getUrl('Login/Login/index'));
            }
        } else
        {
            if ($now < $begin)
            {
                redirect(getUrl('Login/Login/index'));
            }
        }
    }

    /**
     * 验证是否在有效登录时间内
     *
     * @param 用户信息 $user
     */
    public function checkInvalidTime($user,$userrole)
    {
        $result = $this->getProxy('System')->validateLoginTime($user,$userrole);
        if (!$result ['status'])
        {
            redirect(getUrl('Login/Login/index'));
        }
    }

    /**
     * 清除离开保护缓存
     */
    public function clearProtect($userid)
    {
        session ( 'lastOperatorTime', '' );
//		$cacheKey = $userid . '_lastOperatorTime';
//		$this->loginModel->cache_setcache ( $cacheKey, null );
    }

}