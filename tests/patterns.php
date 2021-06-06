<?php

return [
    '/static/123' => 'static route',
    'GET /get/123' => 'get route',
    'POST /post/123' => 'post route',

    'GET /multi/123' => 'multi route - get',
    'PUT /multi/123' => 'multi route - put',

    '/abc/(?<var1>[^/]+)' => 'route with variable',
    '/abc/(?<var1>[^/]+)/123' => 'route with a nested variable',
    '/abc/(?<var1>[^/]+)/(?<var2>[^/]+)' => 'route with two variables',
    '/abc/(?<var1>[^/]+)/def/(?<var2>[^/]+)/action/edit/(?<var3>[^/]+)/end' => 'big messy route',

    '/abc/wild/(.+)' => 'wildcard route',
    '/abc/pre/(?<pre>[^/]+)/(.+)' => 'wildcard route w/ pre',
    '/abc/post/(.+)/(?<post>[^/]+)' => 'wildcard route w/ post',

    '/3rd-last' => 'last - 2',
    '/penultimate/hello' => 'last - 1',
    '/last/(?<world>[^/]+)' => 'last - 0',
];
