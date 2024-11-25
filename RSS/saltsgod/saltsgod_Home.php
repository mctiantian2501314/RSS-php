<?php
/*
作者声明：dy_天天的鸟蛋蛋
仓库首页:https://github.com/mctiantian2501314
仓库地址https://github.com/mctiantian2501314/RSS-php
代码是个人写的。
中途有些地方用的AI(豆包, kimi)
php新人可能写的不好。
也在php学习中。
允许修改。 
但要保留作者信息。

代码介绍: 盐神居爬取首页
订阅RSS php 源码


最后:
那个获取文章 如果把最后的替换删掉(echo  $rss->asXML();)，他死活都获取不到内容了。
我不知道有没有大佬帮我把那个替换弄一弄。
所以我只能用替换的方式来获取文章。

GET 请求 pn= 页码
如pn=2
返回内容
RSS XML
*/
// 设置HTTP响应头，指定内容类型为RSS XML格式，并指定编码为UTF-8
header('Content-Type: application/rss+xml; charset=utf-8');

// 获取页码参数，若未传则默认为1（表示第一页）
$pageNum = isset($_GET['pn']) ? intval($_GET['pn']) : 1;

// 根据页码构建要获取内容的页面URL
$pageUrl = $pageNum >= 2 ? "https://saltsgod.com/blog/page/{$pageNum}" : "https://saltsgod.com/";

// 尝试获取指定URL的页面内容
$htmlContent = @file_get_contents($pageUrl);

// 检查是否成功获取到页面内容，如果失败则输出错误信息并终止脚本执行
if ($htmlContent === false) {
    die('Failed to retrieve the webpage content.');
}

// 创建一个新的DOMDocument对象，用于加载和解析页面HTML内容
$dom = new DOMDocument();
// 使用loadHTML方法加载HTML内容，LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD选项用于防止添加缺失的元素和DOCTYPE
@$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
// 创建DOMXPath对象，用于在DOM文档中进行XPath查询
$xpath = new DOMXPath($dom);

// 定义XML字符串，包含XML声明、命名空间声明和rss根元素
$xmlString = '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="/sheet.xsl"?>
<rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" version="2.0"></rss>';
// 创建SimpleXMLElement对象，用于构建RSS feed
$rss = new SimpleXMLElement($xmlString);

// 为rss元素添加docs属性，指向RSS 2.0规范的URL
$rss->addAttribute('docs', 'https://validator.w3.org/feed/docs/rss2.html');
// 添加generator元素，标识生成RSS feed的工具
$rss->addChild('generator', 'https://github.com/jpmonette/feed');

// 创建channel元素，用于包含RSS feed的频道信息
$channel = $rss->addChild('channel');
// 向channel添加标题、链接、描述、语言和最后更新时间等子元素
$channel->addChild('title', '盐神居');
$channel->addChild('link', $pageUrl);
$channel->addChild('description', '盐神居 - 分享互联网的搬运工');
$channel->addChild('language', 'zh-cn');

// 获取当前世界协调时间并格式化为规范格式，设置lastBuildDate
$lastBuildDateUtc = gmdate('D, d M Y H:i:s O');
$channel->addChild('lastBuildDate', $lastBuildDateUtc);

// 获取当前北京时间并格式化为规范格式，设置lastBuildDate_Beijing
$dateTime = new DateTime('now', new DateTimeZone('Asia/Shanghai'));
$lastBuildDateBeijing = $dateTime->format('D, d M Y H:i:s O');
$channel->addChild('lastBuildDate_Beijing', $lastBuildDateBeijing);

// 使用XPath查询所有文章元素
$articlesXPath = $pageNum >= 2 ? '/html/body/div/div/div/div/main/article' : '/html/body/div/div/div/div/div/article';


$articles = $xpath->query($articlesXPath);

// 检查查询结果是否有效
if ($articles === false) {
    die('Failed to query articles.');
}

// 遍历所有文章元素，为每篇文章创建一个RSS item
foreach ($articles as $article) {
    $item = $channel->addChild('item');
    // 提取并设置文章标题
$titleXPath = $pageNum >= 2 ? './/header[1]/h2[1]/a[1]/child::node()' : './/header[1]/h2[1]/a[1]/child::node()';


    $title = $xpath->query($titleXPath, $article);
    $item->addChild('title', htmlspecialchars($title->length > 0 ? $title->item(0)->nodeValue : '', ENT_XML1));
    // 提取并设置文章链接
    $linkXPath = $pageNum >= 2 ? './/header[1]/h2[1]/a[1]/ancestor-or-self::node()/@href' : './/header[1]/h2[1]/a[1]/@href';

    $link = $xpath->query($linkXPath, $article);
    $link = $link->length > 0 ? $link->item(0)->nodeValue : '';
    if (strpos($link, 'http') !== 0) {
        $link = 'https://saltsgod.com/' . ltrim($link, '/');
    }
    $item->addChild('link', htmlspecialchars($link, ENT_XML1));
    // 提取并设置文章描述
$descriptionXPath = $pageNum >= 2 ? './/div[1]/p[1]/child::node()' : './/div[1]/p[1]/child::node()';

    $description = $xpath->query($descriptionXPath, $article);
    $item->addChild('description', htmlspecialchars($description->length > 0 ? $description->item(0)->nodeValue : '', ENT_XML1));

    // 提取并设置文章发布日期
$dateXPath = $pageNum >= 2 ? './/time[@datetime]/@datetime' : './/header[1]/div[1]/time[@datetime]/@datetime';

    $date = $xpath->query($dateXPath, $article);
    $dateString = $date->length > 0 ? $date->item(0)->nodeValue : '';
    if ($dateString) {
        try {
            $dateTime = new DateTime($dateString);
            $dateTime->setTimezone(new DateTimeZone('Asia/Shanghai'));
            $dateFormatted = $dateTime->format('D, d M Y H:i:s O');
        } catch (Exception $e) {
            $dateFormatted = date('D, d M Y H:i:s O');
        }
    } else {
        $dateFormatted = date('D, d M Y H:i:s O');
    }
    $item->addChild('pubDate', htmlspecialchars($dateFormatted, ENT_XML1));

    // 提取并设置文章GUID
    $guidXPath = $pageNum >= 2 ? './/header[1]/h2[1]/a[1]/ancestor-or-self::node()/@href' : './/header[1]/h2[1]/a[1]/@href';
    $guid = $xpath->query($guidXPath, $article);
    $guid = $guid->length > 0 ? $guid->item(0)->nodeValue : '';
    if (strpos($guid, 'http') !== 0) {
        $guid = 'https://saltsgod.com/' . ltrim($guid, '/');
    }
    $item->addChild('guid', htmlspecialchars($guid, ENT_XML1));

    // 获取文章的详细内容页面
    $articleContent = @file_get_contents($link);
    if ($articleContent !== false) {
        $articleDom = new DOMDocument();
        @$articleDom->loadHTML($articleContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $articleXpath = new DOMXPath($articleDom);
        $contentElement = $articleXpath->query('//*[@id="__blog-post-container"]')->item(0);

        // 处理文章内容中的所有img标签
        $imgTags = $articleXpath->query('//img');
        foreach ($imgTags as $img) {
            $src = $img->getAttribute('src');
           /*判断是否 是base64 如果是那就"原封不动"
           否则 当做原始链接 链接拼接 补齐原始链接
           //把相对链接补为绝对链接
           */
            if (preg_match('/^data:image\/.*;base64,.*$/i', $src)) {
                continue;
            } elseif (strpos($src, 'http') !== 0 && strpos($src, 'https') !== 0) {
                $src = 'https://saltsgod.com/' . ltrim($src, '/');
                $img->setAttribute('src', $src);
            }
        }

        // 获取包含处理后图片的完整HTML内容
        $contentEncoded = $articleDom->saveHTML($contentElement);
        $item->addChild('content:encoded', htmlspecialchars($contentEncoded, ENT_XML1));
    }
}

// 输出构建好的RSS feed XML
$xml = $rss->asXML();
$xml = str_replace('<encoded', '<content:encoded', $xml);
$xml = str_replace('</encoded>', '</content:encoded>', $xml);

echo $xml;


?>
