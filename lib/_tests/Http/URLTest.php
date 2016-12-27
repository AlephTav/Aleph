<?php

use Aleph\Http\URL;
use Aleph\Data\Structures\Container;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for Aleph\Http\URL class.
 *
 * @group http
 */
class URLTest extends TestCase
{
    /**
     * URLs data provider.
     *
     * @return array
     */
    public function urls()
    {
        return [
            [
                '',
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => [],
                    'query' => new Container(),
                    'fragment' => ''
                ]
            ],
            [
                '///path/to//source//',
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => ['path', 'to', 'source'],
                    'query' => new Container(),
                    'fragment' => ''
                ]
            ],
            [
                '/?q1=v1&q2=v2&q3=v3',
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => [],
                    'query' => new Container(['q1' => 'v1', 'q2' => 'v2', 'q3' => 'v3']),
                    'fragment' => ''
                ]
            ],
            [
                '?q=v#some_fragment',
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => [],
                    'query' => new Container(['q' => 'v']),
                    'fragment' => 'some_fragment'
                ]
            ],
            [
                'index.php',
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => ['index.php'],
                    'query' => new Container(),
                    'fragment' => ''
                ]
            ],
            [
                'http://my.host/index.php',
                [
                    'scheme' => 'http',
                    'host' => 'my.host',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => ['index.php'],
                    'query' => new Container(),
                    'fragment' => ''
                ]
            ],
            [
                'https://secret.com#todo',
                [
                    'scheme' => 'https',
                    'host' => 'secret.com',
                    'port' => 443,
                    'user' => '',
                    'password' => '',
                    'path' => [],
                    'query' => new Container(),
                    'fragment' => 'todo'
                ]
            ],
            [
                'https://usver:god@my.test.com:8080/some%2Ftest///path/index.html?v1=val1&v2=val%3F2&v3[]=val31&v3[]=val32#frag%23ment',
                [
                    'scheme' => 'https',
                    'host' => 'my.test.com',
                    'port' => 8080,
                    'user' => 'usver',
                    'password' => 'god',
                    'path' => ['some/test', 'path', 'index.html'],
                    'query' => new Container(['v1' => 'val1', 'v2' => 'val?2', 'v3' => ['val31', 'val32']]),
                    'fragment' => 'frag#ment'
                ]
            ]
        ];
    }

    /**
     * URL parts data provider.
     *
     * @return array
     */
    public function urlParts()
    {
        return [
            [
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => [],
                    'query' => new Container(),
                    'fragment' => ''
                ],
                [
                    URL::SCHEME => '',
                    URL::HOST => '',
                    URL::PATH => '',
                    URL::QUERY => '',
                    URL::FRAGMENT => '',
                    URL::PATH | URL::QUERY => '',
                    URL::HOST | URL::PATH | URL::QUERY => '',
                    URL::ALL => ''
                ]
            ],
            [
                [
                    'scheme' => '',
                    'host' => '',
                    'port' => 80,
                    'user' => '',
                    'password' => '',
                    'path' => ['a', 'b', 'c'],
                    'query' => new Container(),
                    'fragment' => 'frog'
                ],
                [
                    URL::SCHEME => '',
                    URL::HOST => '',
                    URL::PATH => 'a/b/c',
                    URL::QUERY => '',
                    URL::FRAGMENT => 'frog',
                    URL::PATH | URL::QUERY => 'a/b/c',
                    URL::HOST | URL::PATH | URL::QUERY => '/a/b/c',
                    URL::ALL => '/a/b/c#frog'
                ]
            ],
            [
                [
                    'scheme' => '',
                    'host' => 'my.host',
                    'port' => 8080,
                    'user' => '',
                    'password' => '',
                    'path' => ['a', 'b', 'c'],
                    'query' => new Container(),
                    'fragment' => ''
                ],
                [
                    URL::SCHEME => '',
                    URL::HOST => 'my.host:8080',
                    URL::PATH => 'a/b/c',
                    URL::QUERY => '',
                    URL::FRAGMENT => '',
                    URL::PATH | URL::QUERY => 'a/b/c',
                    URL::HOST | URL::PATH | URL::QUERY => 'my.host:8080/a/b/c',
                    URL::ALL => 'my.host:8080/a/b/c'
                ]
            ],
            [
                [
                    'scheme' => 'https',
                    'host' => 'my.host',
                    'port' => 6060,
                    'user' => 'user',
                    'password' => 'pass',
                    'path' => ['very', 'long', 'path', '', '', 'index.php'],
                    'query' => new Container(['a' => 1, 'b' => 2, 'c' => ['a', 'b', 'c' => [1, 2, 3]]]),
                    'fragment' => 'tab'
                ],
                [
                    URL::SCHEME => 'https://',
                    URL::HOST => 'user:pass@my.host:6060',
                    URL::PATH => 'very/long/path/index.php',
                    URL::QUERY => 'a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3',
                    URL::FRAGMENT => 'tab',
                    URL::PATH | URL::QUERY => 'very/long/path/index.php?a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3',
                    URL::HOST | URL::PATH | URL::QUERY => 'user:pass@my.host:6060/very/long/path/index.php?a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3',
                    URL::ALL => 'https://user:pass@my.host:6060/very/long/path/index.php?a=1&b=2&c%5B0%5D=a&c%5B1%5D=b&c%5Bc%5D%5B0%5D=1&c%5Bc%5D%5B1%5D=2&c%5Bc%5D%5B2%5D=3#tab'
                ]
            ]
        ];
    }

    /**
     * @param string $url The url to parse.
     * @param array $props The values of URL object properties after parsing.
     * @covers URL::parse
     * @dataProvider urls
     */
    public function testParse(string $url, array $props)
    {
        $obj = new URL();
        $obj->parse($url);
        foreach ($props as $prop => $value) {
            $this->assertEquals($value, $obj->{$prop});
        }
    }

    /**
     * @param array $props The values of URL object properties.
     * @param string[] $parts The url parts after building.
     * @covers URL:build
     * @dataProvider urlParts
     */
    public function testBuild(array $props, array $parts)
    {
        $obj = new URL();
        foreach ($props as $prop => $value) {
            $obj->{$prop} = $value;
        }
        foreach ($parts as $partID => $value) {
            $this->assertEquals($value, $obj->build($partID));
        }
    }
}