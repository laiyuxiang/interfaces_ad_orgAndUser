<?php
/**
 *基础类
 *
 *用于操作原框架内容
 * @author      laifei 作者
 * @version     1.0 版本号
 */
namespace Interfaces\Controller;
use Think\Controller;
use Unis\UnisTable;

class BaseController extends Controller {

    protected $Abstract;
    protected $abstractRequestHelper;
    /**
     * 架构函数
     * @access public
     */
    public function __construct() {

        $this->Abstract = M ( 'Unis\UnisAbstract:abstract' );
        $this->abstractRequestHelper = $this->Abstract->getContext ()->getRequest ();

        parent::__construct();
    }

    /**
     * 获取Proxy
     *
     * @access public
     * @return ProxyAbstract
     */
    protected function getProxy($proxyname) {
        return $this->Abstract->getProxy ( $proxyname );
    }

    /**
     * 实例化Model对象
     *
     * @access public
     * @return Model
     */
    protected function getModel($name, $app = NULL) {

        if ($app == NULL) $app = MODULE_NAME;
        return $this->Abstract->getModel ( $name, $app );
    }

    /**
     *
     * @param Array $tableConfig
     *        	表示配置（数据项，样式等）
     * @param String $tableName
     *        	数据来源表
     * @param String $where
     *        	sql语句中的条件，以“and” 开头
     * @return Array 返回翻页的数据
     * 增加$bigbatch参数，当此参数为true时，代表该表为数据量特别巨大需要特殊处理。
     * 如需使用大数据查询要求单表和主键id值;修改时间2016-05-18;
     * pagesize 翻页每页显示的数量，不填写默认为20，填写“no”为不翻页
     * page 当前页数。默认为1
     * $listid:列表id
     * 上面两个参数为翻页的关键字，通过前台ajax或者form 提交过来。为必须参数。
     */
    protected function returnPageData($modelName, $tableName, $where, $order = '',$listid='list',$bigbatch=false,$tableid=null) {

        $data = Array();
        $page = 1;
        if (isset ( $_REQUEST ['page'] )&&(!empty($_REQUEST ['page'])))
            $page = $_REQUEST ['page'];
        if(isset ( $_REQUEST ['pagesize'] )&&(!empty($_REQUEST ['pagesize']))){
            //有无分页判断
            $nopage = ($_REQUEST ['pagesize'] == 'no' || empty($_REQUEST ['pagesize']))?true:false;
        }
        $pagesize = $this->getPageSize(array(),$listid);

        $nopage = $pagesize == 'no' ? true :false;
        if(!empty($tableName)){
            $model = $this->getModel ( $modelName );
            //如果总条数大于实际条数设置为第一条

            $data ['total'] = $model->getPageDataCountByTable ( $tableName, $where,$tableid ); // 总条数
            if($page > ceil($data ['total']/$pagesize)){
                $page = '1';
            }
        }
        $start = $pagesize * $page - $pagesize;
        $limit = $start . "," . $pagesize; // mysql的分页处理
        if(empty($tableName)){
            $data['table'] = Array();
            $data ['total'] = 0;
            $data ['page'] = $page; // 当前第几页 页数
            $data ['pagesize'] = $pagesize; // 每页数量
            return $data;
        }
        if (isset($nopage) && $nopage){
            $data ['table'] = $model->getAllDataByTable ( $tableName, $where, $order ,$tableid);
        }else{
            $data ['table'] = $model->getPageDataByTable ( $tableName, $where, $limit, $order,$bigbatch ,$tableid);
            $data ['page'] = $page; // 当前第几页 页数
            $data ['pagesize'] = $pagesize; // 每页数量
        }

        return $data;
    }

    /**
     * 获取并设置列表每页显示数据条数
     * @param array $config 列表配置
     * @param string $listid 列表ＩＤ
     */
    protected function getPageSize($config,$listid='list'){
        $defaultpagesize = UnisTable::$DEFAULTPAGESIZE;

        $cookieKey = ACTION_NAME."_".$listid;
        if(isset ( $_REQUEST ['pagesize'] )&&(!empty($_REQUEST ['pagesize']))){
            $pagesize = $_REQUEST ['pagesize'];
            cookie($cookieKey,$pagesize,$option=array('expire'=>3600*24*7,'prefix'=>'table_'));
        }elseif(cookie('table_'.$cookieKey)) {
            $pagesize = cookie('table_'.$cookieKey);
        }else {
            $pagesize = $config['conf']['pageSize']?$config['conf']['pageSize']:$defaultpagesize;
            cookie($cookieKey,$pagesize,$option=array('expire'=>3600*24*7,'prefix'=>'table_'));
        }
        return $pagesize;
    }

    public function addRowConfig($id,$where,&$data,$extraConfig=false){
        $this->getProxy ( 'Table' )->addRowConfig($id,$where,$data,$extraConfig);
    }

    /**
     *
     * @param Array $tableConfig
     *        	表示配置（数据项，样式等）
     * @param Array $limitData
     *        	要拼装表格的数据
     * @param String $topName
     *        	表格div ID 用于 修饰表格样式，获取表格ID时使用，重要
     * @return Array 根据页面表格的配置和where语句返回经翻页处理的表格数据
     */
    public function getDataByTableConfig($tableConfig, $limitData, $topName = 'list') {
        // 取得翻页数据，然后传递给 buildTable方法进行表格代码生成。

        if('no'===$limitData['pagesize']){ //无分页
            $tableConfig['conf']['pageSize'] = $limitData['total'];
        }else{
            $tableConfig['conf']['pageSize'] =  $limitData['pagesize'];
        }
        $tableConfig['conf']['total'] = $limitData['total'];
        $tableConfig['conf']['page'] = $limitData['page'];
        $tableConfig['conf']['pageNum']= $limitData['page'];
        $limitData ['table'] = $this->buildTable ( $limitData ['table'], $tableConfig, $topName );
        return $limitData;
    }
    /*
 * 构造表格数据
 * $data:数据数组
 * $config:配置数组
 * $top:数据tableid，用于设置列表滚动条，表格行样式等
 */
    function buildTable($data, $config, $top = 'list') {
        $html = $this->getProxy ( 'Table' )->createTablelist ( $data, $config, $top );
        return $html;
    }

}
