<?php
return array(
'1'	=> '<br />网站名:{$SITE_TITLE},公司名称:{$deal.company},'
	   .'<br />甲方id:{$transfer.user_name},甲方真实姓名:{$seller_info.ex_real_name}.甲方身份证号码:{$seller_info.identify_number},甲方联系电话:{$seller_info.mobile},'
	   .'<br />甲方email:{$seller_info.email},甲方个人介绍:{$seller_info.intro},甲方联系方式:{$seller_info.ex_contact},甲方公司:{$seller_info.company},'
	   .'<br />乙方id:{$purchaser_info.user_name},乙方真实姓名:{$purchaser_info.ex_real_name},乙方身份证号码:{$purchaser_info.identify_number},乙方联系电话:{$purchaser_info.mobile}'
	   .'<br />乙方个人介绍:{$purchaser_info.intro},乙方联系方式:{$purchaser_info.ex_contact},乙方公司:{$purchaser_info.company},转让百分比:{$transfer.user_stock},转让金额:{$transfer.price}'
	   .'<br />货币符号:{$CURRENCY_UNIT},格式化数字{function name="number_format" v="参数" f="保留多少小数点"}:{function name="number_format" v=$transfer.load_money f=2}',

'2'	=> '<br />网站名:{$SITE_TITLE},公司名称:{$deal.company},'
	   .'<br />甲方id:{$transfer.user_name},甲方真实姓名:{$seller_info.ex_real_name}.甲方身份证号码:{$seller_info.identify_number},甲方联系电话:{$seller_info.mobile},'
	   .'<br />甲方email:{$seller_info.email},甲方个人介绍:{$seller_info.intro},甲方联系方式:{$seller_info.ex_contact},甲方公司:{$seller_info.company},'
	   .'<br />乙方id:{$purchaser_info.user_name},乙方真实姓名:{$purchaser_info.ex_real_name},乙方身份证号码:{$purchaser_info.identify_number},乙方联系电话:{$purchaser_info.mobile}'
	   .'<br />乙方个人介绍:{$purchaser_info.intro},乙方联系方式:{$purchaser_info.ex_contact},乙方公司:{$purchaser_info.company},转让百分比:{$transfer.user_stock},转让金额:{$transfer.price}'
	   .'<br />货币符号:{$CURRENCY_UNIT},格式化数字{function name="number_format" v="参数" f="保留多少小数点"}:{function name="number_format" v=$transfer.load_money f=2}',	   

'3'	=>	'<br />投资人ID：{$contract.user_id},投资人：{$contract.user_identify_name},投资人身份证号：{$contract.user_identify_number},投资人电话：{$contract.user_mobile},投资金额：{$contract.user_invest_money}'
		.'<br />投资数量:{$contract.user_invest_num},投资百分比：{$contract.user_invest_percent}'
		.'<br />乙方：{$contract.party_b},乙方法定代表人：{$contract.representative_b},乙方联系人:{$contract.contacts_b},乙方联系电话:{$contract.mobile_b}'
		.'<br />乙方指定的第三人姓名/名称:{$contract.party_b_third_name},乙方指定的第三人身份证号/营业执照号:{$contract.party_b_third_number}'
		.'<br />合同编号：{$contract.contract_number},合同签约时间：{$contract.contract_sign_time}'
		.'<br />资金管理人户名:{$contract.money_management_username}，资金管理人开户行:{$contract.money_management_bank}，资金管理人账号:{$contract.money_management_bank_number}'
		.'<br />第三方受托人:{$contract.third_party_trustee}，第三方受托人身份证号:{$contract.third_party_trustee_number}，其它用途金额:{$contract.other_money}'
		.'<br />项目名：{$contract.deal_name},项目ID：{$contract.deal_id}，百发客佣金：{$contract.deal_pay_radio}，' 
		.'<br />资产购置金额/项目众筹:{$contract.deal_support_amount},其它用途金额:{$contract.other_money},投资总额:{$contract.deal_total_invest_money}'
		.'<br />计划处分方式:{$contract.plan_out_type},计划处分价格:{$contract.plan_total_invest_money},计划处分周期:{$contract.plan_cycle}'
		.'<br />百发客佣金:{$contract.deal_pay_radio},备注：{$contract.contract_memo}'
		.'全部投资人清单: 数组$deal_invest_list ，元素：投标时间：pay_time_format,用户名：user_name_format，投资金额：deal_price，份额比例：invest_percent',
		
);
