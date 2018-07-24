<?php
return [
	'view_replace_str' => [
		'__CSSPC__'    => '/../../qdshop/public/static/home/default1/css',
		'__JSPC__'     =>  '/../../qdshop/public/static/home/default1/js',
		'__IMGPC__'     => 	'/../../qdshop/public/static/home/default1/images',
		'__DIYPC__'     => 	'/../../qdshop/public/static/home/default1/diy',
	],
	'template' => [
		'view_path' => __DIR__.'/view/default1/',
	],
	'dispatch_error_tmpl' =>'public/jump',
	'dispatch_success_tmpl' =>'public/jump',
];
