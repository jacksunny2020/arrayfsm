<?php

namespace Jacksunny\ArrayFsm;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 三维数组定义的状态机机制，用于简化指定状态指定角色是否具有某些操作动作的权限管理
 * 三维数组定义
 * [
 *      'created'=>[
 *          'owner'=>['publish','send','cancel','query'],   //当前对象处于created状态时，角色owner可以执行publish,send,cancel和query动作
 *          'partner'=>['query','send'],
 *      ],
 *      'sent'=>[
 *          'owner'=>['accept','reject','query'],
 *          'partner'=>['query'],                           //当前对象处于sent状态时，角色partner可以执行query动作，但是不能执行publish,send,cancel动作
 *      ],
 * ]
 * 其中 created,sent表示状态，定义在 $status_range 数组中
 * 其中 owner,partner表示角色，定义在 $role_range 数组中
 * 其中 publish,send,cancel,query,accept,reject表示操作动作，定义在 $action_range 数组中
 *
 * @author 施朝阳
 * @date 2017-6-13 16:04:24
 */
class Machine {

    /**
     * 状态范围数组
     */
    protected $status_range;

    /**
     * 角色范围数组
     */
    protected $role_range;

    /**
     * 动作范围数组
     */
    protected $action_range;

    /**
     * 是否默认使用所有动作范围数组中定义的动作
     * 设置为true时，如果某个状态某个角色没有指定动作，则这个状态这个角色可以使用action_range中所有动作
     * 设置为false时,如果某个状态某个角色没有指定动作，则这个状态这个角色不能使用任何动作，有指定动作时可以使用指定动作
     */
    protected $default_all_act;

    /**
     * 是否默认不许使用任何动作范围数组中定义的动作
     * 设置为true时,如果某个状态某个角色没有指定动作，则这个状态这个角色不能使用任何动作
     * 设置为false时,如果某个状态某个角色没有指定动作，则这个状态这个角色不能使用任何动作，有指定动作时可以使用指定动作
     */
    protected $default_none_act;

    /**
     * 保存了状态-角色-动作的三维数组，一般用于确定指定状态指定角色是否具有操作某些动作的权限
     */
    protected $status_role_action_binding;

    /**
     * 构造时需要根据需求通过方法来配置不同状态下不同角色可以执行的动作，而不是通过常量数组方式来定义
     * $default_all_act 默认是不是可以使用全部动作还是默认不允许使用任何动作
     * $this->emptyStatusRoleAllAction($status, $role);
     * $this->bindOnlyStatusRoleAction($status, $role, $action);
     * $this->unbindStatusRoleAction($status, $role, $action);
     * $this->bindAppendStatusRoleAction($status, $role, $another_action)
     */
    public function __construct(array $status_range, array $role_range, array $action_range, $default_all_act = true, array $original_array = null) {
        $this->status_range = $status_range;
        $this->role_range = $role_range;
        $this->action_range = $action_range;
        $this->default_all_act = $default_all_act;
        $this->default_none_act = !$default_all_act;

        if (isset($this->status_range)) {
            foreach ($this->status_range as $status) {
                $this->status_role_action_binding[$status] = $this->role_range;
                foreach ($this->status_role_action_binding[$status] as $code) {
                    $this->status_role_action_binding[$status]["$code"] = null;
                }
            }
        }
        $this->mainInit($original_array);
    }

    /**
     * 主要初始化
     * 用于将指定的外部三维数组设置到系统中，或者通过绑定解绑方法来设置调整状态机三维数组
     */
    public function mainInit($original_array = null) {
        if (isset($original_array)) {
            $this->status_role_action_binding = $original_array;
        } else {
            $this->bindInit();
        }
    }

    /**
     * 通过绑定或解绑来设置状态机三维数组
     */
    protected function bindInit() {
        
    }

    /**
     * 读取状态范围数组
     */
    public function getStatusRange() {
        return $this->status_range;
    }

    /**
     * 读取角色范围数组
     */
    public function getRoleRange() {
        return $this->role_range;
    }

    /**
     * 读取动作范围数组
     */
    public function getActionRange() {
        return $this->action_range;
    }

    /**
     * 查询指定状态下的所有角色和动作二维数组
     */
    public function listStatusRoles($status) {
        return $this->status_role_action_binding[$status];
    }

    /**
     * 查询指定状态和角色对应的可操作动作数组
     */
    public function listStatusRoleActions($status, $role) {
        if (!$this->isExistStatus($status)) {
            return array();
        }
        $status_role = $this->status_role_action_binding[$status];
        if (isset($status_role) && in_array($role, $status_role)) {
            if (array_key_exists($role, $status_role)) {
                if (isset($this->status_role_action_binding[$status][$role])) {
                    return $this->status_role_action_binding[$status][$role];
                }
            }
        }
        //default condition
        if ($this->default_none_act) {
            return array();
        } else if ($this->default_all_act) {
            return $this->getActionRange();
        } else {
            //most default is none action
            return array();
        }
    }

    /**
     * 清除指定状态和角色的所有可操作动作，清除后如果$default_all_act=true则动作范围内所有动作可用，如果$default_none_act=true则没有任何动作可用
     */
    public function doClearStatusRoleAllAction($status, $role = null) {
        $status_role = $this->status_role_action_binding[$status];
        if (isset($role)) {
            if (isset($status_role) && in_array($role, $status_role)) {
                $this->status_role_action_binding[$status][$role] = array();
            }
        } else {
            foreach ($this->role_range as $therole) {
                if (isset($status_role) && in_array($therole, $status_role)) {
                    $this->status_role_action_binding[$status][$therole] = array();
                }
            }
        }
        return $this;
    }

    /**
     * 设置指定状态指定角色只能使用某些操作动作，如果之前该状态该角色有设置其他操作动作，则会被这些操作动作$actions覆盖
     */
    public function doBindOnlyStatusRoleAction($status, $role, $actions) {
        $status_role = $this->status_role_action_binding[$status];
        if (isset($status_role) && in_array($role, $status_role)) {
            if (is_array($actions)) {
                $this->status_role_action_binding[$status][$role] = $actions;
            } else {
                $this->status_role_action_binding[$status][$role] = array($actions);
            }
        }
        return $this;
    }

    public function doBindAppendStatusAllRoleActions($status, $actions) {
        foreach ($this->role_range as $role) {
            $this->doBindAppendStatusRoleAction($status, $role, $actions);
        }
    }

    /**
     * 设置指定状态指定角色的可操作动作中添加指定的某些操作动作$actions，如果之前该状态角色有设置其他操作动作，会添加更多，重复名称的动作会自动忽略掉
     */
    public function doBindAppendStatusRoleAction($status, $role, $actions) {
        $status_roles = $this->status_role_action_binding[$status];
        if (isset($status_roles) && in_array($role, $status_roles)) {
            $array = $this->status_role_action_binding[$status][$role] ?? array();
            if (is_array($actions)) {
                if (isset($this->status_role_action_binding[$status][$role])) {
                    $this->status_role_action_binding[$status][$role] = array_merge($this->status_role_action_binding[$status][$role], $actions);
                } else {
                    $this->status_role_action_binding[$status][$role] = $actions;
                }
            } else {
                if (isset($this->status_role_action_binding[$status][$role])) {
                    array_push($this->status_role_action_binding[$status][$role], $actions);
                } else {
                    $this->status_role_action_binding[$status][$role] = array();
                    array_push($this->status_role_action_binding[$status][$role], $actions);
                }
            }
            $this->status_role_action_binding[$status][$role] = array_unique($this->status_role_action_binding[$status][$role]);
        }
        return $this;
    }

    /**
     * 从指定状态指定角色的指定动作范围数组中去掉$actions指定的所有操作动作，如果要去掉所有动作请使用 doClearStatusRoleAllAction 方法
     */
    public function doUnbindStatusRoleAction($status, $role, $actions) {
        $status_role = $this->status_role_action_binding[$status];
        if (isset($status_role) && in_array($role, $status_role)) {
            $array = $this->status_role_action_binding[$status][$role];
            if (!is_array($actions)) {
                $actions = array($actions);
            }
            for ($i = 0; $i < count($array); $i++) {
                $item = $array[$i];
                foreach ($actions as $action) {
                    if ($item == $action) {
                        //$this->status_role_action_binding[$status][$role] = null;
                        //$array[$i] = null;
                        array_splice($array, $i, 1);
                    }
                }
            }
            $this->status_role_action_binding[$status][$role] = $array;
        }
        return $this;
    }

    /**
     * 判断指定的状态指定角色是否有操作$action动作的权限，有权限返回true，没有权限返回false
     */
    public function isAllowedStatusRoleAction($status, $role, $action) {
        $allow_status_role_actions = $this->listStatusRoleActions($status, $role);
        if (!isset($allow_status_role_actions)) {
            return false;
        }
        return in_array($action, $allow_status_role_actions);
    }

    /**
     * 是否存在指定状态名称$status的规则
     */
    public function isExistStatus($status) {
        if (!isset($status)) {
            return false;
        }
        if (!in_array($status, $this->status_range)) {
            return false;
        }
        return true;
    }

    /**
     * 指定的状态是否是状态机规则中有效的状态，目前只要是存在的状态都认为是有效的
     */
    public function isValidStatus($status) {
        return $this->isExistStatus($status);
    }

}
