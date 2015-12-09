<?php

// +----------------------------------------------------------------------
// | date: 2015-09-18
// +----------------------------------------------------------------------
// | AdminMenuModel.php: 后台菜单模型
// +----------------------------------------------------------------------
// | Author: zhuweijian <zhuweijain@louxia100.com>
// +----------------------------------------------------------------------

namespace App\Model\Admin\Admin;

class AdminMenuModel extends BaseModel
{

    protected $table    = 'admin_menu';//定义表名
    private static $all_menu = null;//全部菜单

    /**
     * 获得全部菜单导航
     *
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getAll()
    {
        if (is_null(self::$all_menu)) {
            self::$all_menu = self::mergeData(self::all());
        }
        return self::$all_menu;
    }

    /**
     * 组合数据
     *
     * @param $roles
     * @return mixed
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function mergeData($data)
    {
        if (!empty($data)) {
            foreach ($data as &$v) {

                //组合操作
                $v->handle       = '<a href="'.createUrl('Admin\Admin\AdminMenuController@getEdit',['id' => $v->id]).'" >修改</a>';
                //父级菜单
                if ($v['parent_id']) {
                    $v['parent_name'] = self::multiwhere( ['id' => $v['parent_id']] )->pluck('menu_name');
                } else if($v['parent_id'] == 0) {
                    $v['parent_name'] = trans('response.top_classification');
                }

            }
        }
        return $data;
    }

    /**
     * 获得组合用户全部菜单 [组合好]
     *
     * @param null $role_id
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getFullUserMenu($limit_id = null)
    {
        $limit_id       = AdminInfoModel::getAdminLimit($limit_id);
        $all_menu       = self::getAll();
        $all_user_menu  = AdminLimitMenuModel::getUserRelationMenu($limit_id);

        if (!empty($all_menu)) {
            foreach ($all_menu as &$menu) {
                $menu->checked = in_array($menu->id, $all_user_menu) ? true : false;
            }
        }
        //组合数据
        return arrayToObj(mergeTreeChildNode(objToArray($all_menu), 0, 0, 'parent_id'));
    }

    /**
     * 获得顶级菜单
     *
     * @return array|bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getAdminTopMenu($limit_id = null)
    {
        //获得当前角色全部menu_id
        $all_menu_id = AdminLimitMenuModel::getAdminAllMenuId($limit_id);

        if (empty($all_menu_id)) {
            return false;
        }
        return self::mergeMenuUrl(self::multiwhere(['id' => ['IN', $all_menu_id], 'parent_id' => 0])->orderBy('sort', 'asc')->get());
    }

    /**
     * 获得当前用户菜单
     *
     * @param $parent_id
     * @param $limit_id
     * @return array|bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getAdminMenu($parent_id = 0, $limit_id = null)
    {
        //获得当前角色全部menu_id
        $all_menu_id = AdminLimitMenuModel::getAdminAllMenuId($limit_id);

        if (empty($all_menu_id)) {
            return false;
        }
        return mergeTreeChildNode(objToArray(
            self::mergeMenuUrl(self::multiwhere(['parent_id' => ['>', 0], 'id' => ['IN', $all_menu_id]] )->
            orderBy('sort', 'asc')->
            get()
            )), $parent_id);
    }

    /**
     * 组合url
     *
     * @param $menu_list
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    private static function mergeMenuUrl($menu_list)
    {
        if (empty($menu_list)) {
            return false;
        }

        foreach ($menu_list as &$menu) {
            if (empty($menu->menu_url)) continue;

            $menu->menu_url = createUrl($menu->menu_url);
        }
        return $menu_list;
    }

    /**
     * 获得当前用户全部菜单--递归（左侧菜单显示）
     *
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getUserMenuSide()
    {
        return mergeTreeNode(objToArray(self::getAll()), 0, 0, 'parent_id');
    }

    /**
     * 获得当前路由的菜单id
     *
     * @param $menu_route
     * @return bool
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function getMenuId($menu_route)
    {
        if (!empty($menu_route)) {
            return self::multiwhere( ['menu_url' => $menu_route] )->pluck('id');
        }
        return false;
    }

    /**
     * 组合后台当前位置
     *
     * @param $menu_id
     * @return array
     * @author yangyifan <yangyifanphp@gmail.com>
     */
    public static function mergeLocation($menu_id)
    {
        $all_menu = self::getAll();//获得全部路由

        return array_reverse(getLocation($all_menu, $menu_id));
    }

}