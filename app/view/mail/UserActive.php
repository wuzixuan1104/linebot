<div style='display:inline-block;width:100%;height:28px;line-height:28px;'>親愛的 <b><?php echo $user->name;?></b>，您好：</div>
<div style='display:inline-block;width:100%;height:28px;line-height:28px;'>歡迎加入 <b>AD-POST</b></div>
<div style='display:inline-block;width:100%;height:28px;line-height:28px;'>請點擊以下連結，驗證您的會員信箱：</div>
<div style='display:inline-block;width:100%;height:28px;line-height:28px;'><a href='<?php echo URL::base ('register/verify?id=' . $user->id . '&code=' . $active->token);?>' target='_blank' style='color: rgba(58, 122, 243, 1.00)'><?php echo URL::base ('register/verify?id=' . $user->id . '&code=' . $active->token);?></a></div>