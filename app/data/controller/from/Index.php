<?php
namespace app\data\controller\from;
use app\data\model\DataConfigApi;
use app\data\model\DataFrom;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
class Index extends Controller
{

    /**
     * 表单管理
     * @auth true  # 表示需要验证权限
     * @menu true  # 添加系统菜单节点
     * @login true # 强制登录才可访问
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundExceptio
     */
    public function  index(){
        DataFrom::mQuery()->layTable(function () {
            $this->title = '表单管理';
        }, function (QueryHelper $query) {
            $query->where(['deleted' => 0])->equal('status')->like('name,phone,wechat,status');
            $query->order('sort desc,create_time desc');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 列表数据处理
     * @auth true
     * @param array $data
     * @throws \Exception
     */
    protected function _index_page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            $vo['zhid'] = DataConfigApi::mk()->where(['id'=>$vo['zhid']])->column('name')[0];
        }
    }
}