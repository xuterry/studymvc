<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Score extends Index
{
 
    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
                                       // 查询插件表
        $r = $this->getModel('SignActivity')->fetchAll();
        if (! empty($r)) {
            foreach ($r as $k => $v) {
                if ($v->image == '') {
                    $v->image = 'nopic.jpg';
                }
            }
        }
        $this->assign("uploadImg", $uploadImg);
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $this->assign("uploadImg", $uploadImg);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $image = addslashes($request->param('image')); // 活动图片
        $starttime = $request->param('starttime'); // 活动开始时间
        $endtime = $request->param('endtime'); // 活动结束时间
        $detail = addslashes(trim($request->param('detail'))); // 活动介绍
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
        } else {
            $this->error('签到活动图片不能为空！', '');
        }
        if ($starttime == '') {
            $this->error('活动开始时间不能为空！', '');
        }
        $starttime = date('Y-m-d H:i:s', strtotime($starttime));
        if ($endtime == '') {
            $this->error('活动结束时间不能为空！', '');
        }
        $endtime = date('Y-m-d 23:59:59', strtotime($endtime));
        
        if ($starttime >= $endtime) {
            $this->error('活动开始时间不能大于等于活动结束时间！', '');
        }
        
        $time = date('Y-m-d H:i:s');
        if ($time >= $endtime) {
            $this->error('活动还没开始就已经结束！', '');
        }
        // 查询所有签到活动
        $r = $this->getModel('SignActivity')->fetchAll('*');
        if ($r) {
            for ($i = 0; $i < count($r); $i ++) {
                if ($starttime >= $r[$i]->starttime && $starttime < $r[$i]->endtime || $endtime > $r[$i]->starttime && $endtime <= $r[$i]->endtime) {
                    $this->error('活动有冲突！', '');
                }
            }
        }
        // 活动开始时间大于当前时间,活动还没开始
        if ($starttime > $time) {
            // 添加活动
            $rr = $this->getModel('SignActivity')->insert([
                                                                'image' => $image,'starttime' => $starttime,'endtime' => $endtime,'detail' => $detail,'add_time' => nowDate(),'status' => 0
            ]);
        } else {
            // 添加活动
            $rr = $this->getModel('SignActivity')->insert([
                                                                'image' => $image,'starttime' => $starttime,'endtime' => $endtime,'detail' => $detail,'add_time' => nowDate(),'status' => 1
            ]);
        }
        if ($rr == false) {
            $this->error('未知原因，活动添加失败！', $this->module_url . "/sign");
        } else {
            $this->success('活动添加成功！', $this->module_url . "/sign");
        }
    }

    private function do_configs($request)
    {
        
        // 接收信息
        $plug_ins_id = intval($request->param('plug_ins_id'));
        // $imgurl= addslashes($request->param('imgurl')); // 活动图片
        $imgurl = addslashes($request->param('imgurl')); // 新活动图片
        $oldpic = addslashes($request->param('oldpic')); // 原活动图片
        $min_score = addslashes(trim($request->param('min_score'))); // 领取的最少积分
        $max_score = addslashes(trim($request->param('max_score'))); // 领取的最大积分
        $continuity_three = addslashes(trim($request->param('continuity_three'))); // 连续签到7天
        $continuity_twenty = addslashes(trim($request->param('continuity_twenty'))); // 连续签到20天
        $continuity_thirty = addslashes(trim($request->param('continuity_thirty'))); // 连续签到30天
        $activity_overdue = addslashes(trim($request->param('activity_overdue'))); // 活动过期删除时间
        
        if ($imgurl) {
            $imgurl = preg_replace('/.*\//', '', $imgurl);
            if ($imgurl != $oldpic) {
                $uploadImg=Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg;
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg . $oldpic));
            }
        } else {
            $imgurl = $oldpic;
        }
        
        if (is_numeric($min_score) == '') {
            $this->error('领取的最少积分请输入数字!', '');
        }
        if ($min_score <= 0) {
            $this->error('领取的最少积分不能为负数或0!', '');
        }
        if (is_numeric($max_score) == '') {
            $this->error('领取的最少积分请输入数字!', '');
        }
        if ($max_score <= 0) {
            $this->error('领取的最少积分不能为负数或0!', '');
        }
        if ($max_score < $min_score) {
            $this->error('领取的最大积分不能小于领取的最少积分!', '');
        }
        if ($continuity_three) {
            if (is_numeric($continuity_three) == '') {
                $this->error('连续签到7天请输入数字!', '');
            }
            if ($continuity_three < 0) {
                $this->error('连续签到7天不能为负数!', '');
            }
        }
        if ($continuity_twenty) {
            if (is_numeric($continuity_twenty) == '') {
                $this->error('连续签到20天请输入数字!', '');
            }
            if ($continuity_twenty < 0) {
                $this->error('连续签到20天不能为负数!', '');
            }
        }
        if ($continuity_thirty) {
            if (is_numeric($continuity_thirty) == '') {
                $this->error('连续签到30天请输入数字!', '');
            }
            if ($continuity_thirty < 0) {
                $this->error('连续签到30天不能为负数!', '');
            }
        }
        if ($activity_overdue) {
            if (is_numeric($activity_overdue) == '') {
                $this->error('活动过期删除时间请输入数字!', '');
            }
            if ($activity_overdue < 0) {
                $this->error('活动过期删除时间不能为负数!', '');
            }
        }
        
        $r = $this->getModel('SignConfig')
            ->where([
                        'plug_ins_id' => [
                                            '=',$plug_ins_id
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $r_1 = $this->getModel('SignConfig')->saveAll([
                                                                'imgurl' => $imgurl,'activity_overdue' => $activity_overdue,'min_score' => $min_score,'max_score' => $max_score,'continuity_three' => $continuity_three,'continuity_twenty' => $continuity_twenty,'continuity_thirty' => $continuity_thirty,'modify_date' => nowDate()
            ], [
                    'plug_ins_id' => [
                                        '=',$plug_ins_id
                    ]
            ]);
            if ($r_1 == false) {
                $this->error('未知原因，签到参数修改失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('签到参数修改成功！', $this->module_url . "/plug_ins");
            }
        } else {
            $r_1 = $this->getModel('SignConfig')->insert([
                                                            'plug_ins_id' => $plug_ins_id,'imgurl' => $imgurl,'activity_overdue' => $activity_overdue,'min_score' => $min_score,'max_score' => $max_score,'continuity_three' => $continuity_three,'continuity_twenty' => $continuity_twenty,'continuity_thirty' => $continuity_thirty,'modify_date' => nowDate()
            ]);
            if ($r_1 == false) {
                $this->error('未知原因，签到参数添加失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('签到参数添加成功！', $this->module_url . "/plug_ins");
            }
        }
        return;
    }

    public function configs(Request $request)
    {
        $plug_ins_id = intval($request->param("id")); // 插件id
        $r = $this->getConfig();
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        
        $r = $this->getModel('SignConfig')->get($plug_ins_id, 'plug_ins_id ');
        if ($r) {
            $imgurl = $r[0]->imgurl; // 签到图片
            $min_score = $r[0]->min_score; // 领取的最少积分
            $max_score = $r[0]->max_score; // 领取的最大积分
            $continuity_three = $r[0]->continuity_three; // 连续签到7天
            $continuity_twenty = $r[0]->continuity_twenty; // 连续签到20天
            $continuity_thirty = $r[0]->continuity_thirty; // 连续签到30天
            $activity_overdue = $r[0]->activity_overdue; // 活动过期删除时间
        } else {
            $imgurl = ''; // 签到图片
            $min_score = 1;
            $max_score = 10;
            $continuity_three = 5;
            $continuity_twenty = 10;
            $continuity_thirty = 20;
            $activity_overdue = 2;
        }
        $this->assign("uploadImg", $uploadImg);
        $this->assign('plug_ins_id', $plug_ins_id);
        $this->assign('imgurl', isset($imgurl) ? $imgurl : '');
        $this->assign('min_score', $min_score);
        $this->assign('max_score', $max_score);
        $this->assign('continuity_three', $continuity_three);
        $this->assign('continuity_twenty', $continuity_twenty);
        $this->assign('continuity_thirty', $continuity_thirty);
        $this->assign('activity_overdue', $activity_overdue);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        $request->method() == 'post' && $this->do_del($request);
        
        return;
    }

    private function do_del($request)
    {
        
        // 接收信息
        $id = intval($request->param('id')); // 插件id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        
        $r = $this->getModel('SignActivity')->get($id, 'id');
        $image = $r[0]->image;
        // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$image));
        
        $res=$this->getModel('SignActivity')->delete($id,'id');
        if ($res > 0) {
            $this->success('删除成功！', $this->module_url . "/sign");
            return;
        }
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = intval($request->param("id")); // 活动id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
                                                                     // 根据插件id，查询插件信息
        $res = $this->getModel('SignActivity')->get($id, 'id');
        if ($res) {
            $id = $res[0]->id;
            $image = $res[0]->image;
            $starttime = $res[0]->starttime;
            $endtime = $res[0]->endtime;
            $detail = $res[0]->detail;
            $status = $res[0]->status;
        }
        
        $this->assign("uploadImg", $uploadImg);
        $this->assign("id", $id);
        $this->assign("image", $image);
        $this->assign("starttime", $starttime);
        $this->assign("endtime", $endtime);
        $this->assign("detail", $detail);
        $this->assign("status", $status);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $id = addslashes(trim($request->param('id'))); // 签到活动id
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $image = addslashes(trim($request->param('image'))); // 新活动图片
        $oldpic = addslashes(trim($request->param('oldpic'))); // 原活动图片
        $starttime = $request->param('starttime'); // 活动开始时间
        $endtime = $request->param('endtime'); // 活动结束时间
        $detail = addslashes(trim($request->param('detail'))); // 活动介绍
        
        if ($image) {
            $image = preg_replace('/.*\//', '', $image);
            if ($image != $oldpic) {
                // @unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        } else {
            $image = $oldpic;
        }
        if ($starttime == '') {
            $this->error('活动开始时间不能为空！', '');
        }
        $starttime = date('Y-m-d H:i:s', strtotime($starttime));
        if ($endtime == '') {
            $this->error('活动结束时间不能为空！', '');
        }
        $endtime = date('Y-m-d 23:59:59', strtotime($endtime));
        if ($starttime >= $endtime) {
            $this->error('活动开始时间不能大于等于活动结束时间！', '');
        }
        $time = date('Y-m-d H:i:s');
        if ($time >= $endtime) {
            $this->error('活动还没开始就已经结束！', '');
        }
        // 查询所有签到活动
        $r = $this->getModel('SignActivity')
            ->where('id', '<>', $id)
            ->fetchAll();
        if ($r) {
            for ($i = 0; $i < count($r); $i ++) {
                if ($starttime >= $r[$i]->starttime && $starttime < $r[$i]->endtime || $endtime > $r[$i]->starttime && $endtime <= $r[$i]->endtime) {
                    $this->error('活动有冲突！', '');
                }
            }
        }
        if ($starttime > $time) {
            // 更新数据表
            $r=$this->getModel('SignActivity')->saveAll(['image'=>$image,'starttime'=>$starttime,'endtime'=>$endtime,'detail'=>$detail,'add_time'=>$time,'status'=>0],['id'=>['=',$id]]);
        } else {
            // 更新数据表
            $r=$this->getModel('SignActivity')->saveAll(['image'=>$image,'starttime'=>$starttime,'endtime'=>$endtime,'detail'=>$detail,'add_time'=>$time,'status'=>1],['id'=>['=',$id]]);
        }
        if ($r == false) {
            $this->error('未知原因，活动修改失败！', $this->module_url . "/sign");
        } else {
            $this->success('活动修改成功！', $this->module_url . "/sign");
        }
    }

    public function record(Request $request)
    {
        $user_id = addslashes(trim($request->param('user_id'))); // 用户id
        
        $condition = '1 = 1';
        if ($user_id != '') {
            $condition .= " and user_id like '$user_id'";
        }
        $r=$this->getModel('SignRecord')->where($condition." and type = '0'")->fetchAll('*');
        if ($r) {
            $list = $r;
        } else {
            $list = '';
        }
        $this->assign("list", $list);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function setscore(Request $request)
    {
        $request->method() == 'post' && $this->do_setscore($request);
        
        $res=$this->getModel('Setscore')->fetchOrder(['lever'=>'asc'],'lever,ordernum,scorenum');
        $bili = '';
        $str = '[';
        // $res = array_reverse($res);
        foreach ($res as $k => $v) {
            if ($v->lever == -1) {
                $bili = $v->ordernum;
                unset($res[$k]);
            } else {
                $str .= '{"lever":' . $v->lever . ',"ordernum":' . $v->ordernum . ',"scorenum":' . (empty($v->scorenum)?'0':$v->scorenum). '},';
            }
        }
        $str = $str=='['?'[]':substr($str, 0, - 1) . ']';
        $this->assign("bili", $bili);
        $this->assign("res", $str);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_setscore($request)
    {
        $bili = addslashes(trim($request->param('bili')));
        $data = json_decode($request->param('data'));
        $del = $this->getModel('setscore')->deleteWhere(['id'=>['>',0]]);
        
        $ist=$this->getModel('Setscore')->insert(['lever'=>-1,'ordernum'=>$bili]);
        foreach ($data as $k => $v) {
            $arr = explode('~', $v);
            $buy=$this->getModel('Setscore')->insert(['lever'=>$arr[1],'ordernum'=>$k,'scorenum'=>$arr[0]]);
        }
        if ($ist > 0 && $buy > 0) {
            echo json_encode(array(
                                    'code' => 1
            ));
            exit();
        }
        exit(json_encode(['code'=>-1]));
    }

    function changePassword(Request $request)
    {
        $this->redirect($this->module_url . '/index/changePassword');
    }

    function maskContent(Request $request)
    {
        $this->redirect($this->module_url . '/index/maskContent');
    }
}