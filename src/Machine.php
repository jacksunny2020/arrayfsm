<?php

namespace Jacksunny\ArrayFsm;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ArrayFSM
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
     * 默认使用所有动作
     */
    protected $default_all_act;

    /**
     * 默认不许使用任何动作
     */
    protected $default_none_act;
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

    public function mainInit($original_array = null) {
        if (isset($original_array)) {
            $this->status_role_action_binding = $original_array;
        } else {
            $this->bindInit();
        }
    }
    
    protected function bindInit(){
        
    }

    public function getStatusRange() {
        return $this->status_range;
    }

    public function getRoleRange() {
        return $this->role_range;
    }

    public function getActionRange() {
        return $this->action_range;
    }

    public function listStatusRoles($status) {
        return $this->status_role_action_binding[$status];
    }

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

    public function isAllowedStatusRoleAction($status, $role, $action) {
        $allow_status_role_actions = $this->listStatusRoleActions($status, $role);
        if (!isset($allow_status_role_actions)) {
            return false;
        }
        return in_array($action, $allow_status_role_actions);
    }

    public function isExistStatus($status) {
        if (!isset($status)) {
            return false;
        }
        if (!in_array($status, $this->status_range)) {
            return false;
        }
        return true;
    }

    public function isValidStatus($status) {
        return $this->isExistStatus($status);
    }

}
