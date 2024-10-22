# ArticleAssistant
# 关于插件
最新版本：1.0.0
ArticleAssistant 是一个方便你在typecho使用chatgpt进行文章创作的插件！

# 主要功能

> 后台配置自己的API 接口 和 Key
>
> 可以实现上下文连续对话
>
> 一键删除聊天记录
>
> 输入框自适应高度
>
> ...

# 插件安装/使用方法
将本插件上传至typecho中的/usr/plugins目录里面，将名字改成 ArticleAssistant 然后去后台开启插件，对插件进行相应的配置。
然后找到typecho根目录中的admin/write-post文件中，找到下面这段代码

```php
<p>
<label for="text" class="sr-only"><?php _e('文章内容'); ?></label>
```
在这段代码上面加上以下代码
```php
<?php \Typecho\Plugin::factory('admin/write-post.php')->aiWrite(); ?>
```
您可以前往撰写文章页面，体验强大的ai给您带来的写作灵感！
无需科学上网，仅需在你的博客后台即可使用！

# 样式![ArticleAssistant-1](https://github.com/iTTroll52/TypechoArticleAssistant/blob/main/images/ArticleAssistant-1.png)

![ArticleAssistant-2](https://github.com/iTTroll52/TypechoArticleAssistant/blob/main/images/ArticleAssistant-2.png)
