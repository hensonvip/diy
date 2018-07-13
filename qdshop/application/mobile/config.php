<?php
return [
	'view_replace_str' => [
		'__CSSWAP__'    => '/../../qdshop/public/static/mobile/default1/css',
		'__JSWAP__'     =>  '/../../qdshop/public/static/mobile/default1/js',
		'__IMGWAP__'     => 	'/../../qdshop/public/static/mobile/default1/images',
		'__EXTENDWAP__'     => 	'/../../qdshop/public/static/mobile/default1/extend',
	],
	'template' => [
		'view_path' => __DIR__.'/view/default1/',
	],
	'dispatch_error_tmpl' =>'public/error',
	'dispatch_success_tmpl' =>'public/success',
];
 