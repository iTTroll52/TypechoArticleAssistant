<?php
/**
 * Typecho 文章助手
 * @package ArticleAssistant
 * @version 1.0
 */
class ArticleAssistant_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     */
    public static function activate()
    {
        Typecho_Plugin::factory('admin/write-post.php')->aiWrite = array('ArticleAssistant_Plugin', 'render');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     */
    public static function deactivate()
    {
    }

    /**
     * 获取插件配置面板
     *
     * @param Form $form 配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $apiUrl = new Typecho_Widget_Helper_Form_Element_Text('apiUrl', NULL, "https://www.xxx/v1/chat/completions",
        _t('API 地址 (v1/chat/completions)'), _t('请填写 OpenAI API 地址'));
        $form->addInput($apiUrl->addRule('required', _t('API 地址不能为空')));
        
        $apiKey = new Typecho_Widget_Helper_Form_Element_Text('apiKey', NULL, "sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        _t('API Key'), _t('请填写 OpenAI API Key'));
        $form->addInput($apiKey->addRule('required', _t('API Key 不能为空')));
        
        $modelOptions = array(
            'gpt-4o' => 'gpt-4o',
            'gpt-4o-all' => 'gpt-4o-all',
            'gpt-4o-mini' => 'gpt-4o-mini',
        );
        $model = new Typecho_Widget_Helper_Form_Element_Select('model', $modelOptions, 'gpt-4o',
        _t('模型'), _t('请填写要使用的模型名称，默认是gpt-4o'));
        $form->addInput($model->addRule('required', _t('模型名称不能为空')));

        $temperature = new Typecho_Widget_Helper_Form_Element_Text('temperature', NULL, '0.7',
        _t('温度'), _t('请填写生成内容的温度，默认是0.7'));
        $form->addInput($temperature->addRule('required', _t('温度不能为空')));

        $maxTokens = new Typecho_Widget_Helper_Form_Element_Text('maxTokens', NULL, '4000',
        _t('最大Token'), _t('请填写生成内容的最大Token数，默认是4000'));
        $form->addInput($maxTokens->addRule('required', _t('最大Token数不能为空')));
    }

    /**
     * 个人用户的配置面板
     *
     * @param Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        $apiUrl = Helper::options()->plugin('ArticleAssistant')->apiUrl;
        $apiKey = Helper::options()->plugin('ArticleAssistant')->apiKey;
        $model = Helper::options()->plugin('ArticleAssistant')->model;
        $temperature = Helper::options()->plugin('ArticleAssistant')->temperature;
        $maxTokens = Helper::options()->plugin('ArticleAssistant')->maxTokens;

        echo <<<EOF
            <script type="text/javascript">
                function showAiContent(){
                  if(document.getElementById("AiWriteAsk").style.display=="none"){
                      document.getElementById("AiWriteAsk").style.display="block"; 
                      document.getElementById("blog_respone_div").style.display="block";
                      document.getElementById("aiWrite-button").innerHTML="收起AI写作"; 
                  }
                  else{
                      document.getElementById("AiWriteAsk").style.display="none";
                      document.getElementById("blog_respone_div").style.display="none";
                      document.getElementById("aiWrite-button").innerHTML="展开AI写作"; 
                  }
               }
               
               function adjustTextareaHeight(textarea) {
                    textarea.style.height = 'auto'; // 先重置高度
                    textarea.style.height = (textarea.scrollHeight) + 'px'; // 重新设置高度
                }
            
                document.addEventListener('DOMContentLoaded', function() {
                    const keywordsTextarea = document.getElementById('keywords');
                    keywordsTextarea.style.height = '35px'; // 设置初始高度
                    keywordsTextarea.addEventListener('input', function() {
                        adjustTextareaHeight(keywordsTextarea);
                    });
                });
    
               function copyContent() {
                    const range = document.createRange();
                    range.selectNode(document.getElementById('blog_respone'));
                    const selection = window.getSelection();
                    if(selection.rangeCount > 0) selection.removeAllRanges();
                    selection.addRange(range);
                    document.execCommand('copy');
               }
               function clearConversation() {
                    localStorage.removeItem('aiConversation');
                    document.getElementById("blog_respone").value = '';
               }
               if(document.getElementById('copy')) {
                    document.getElementById('copy').addEventListener('click', copyArticle, false);
               }
               function showTemporaryAlert(message, duration) {
                    var alertBox = document.createElement("div");
                    alertBox.style.position = "fixed";
                    alertBox.style.top = "20%";
                    alertBox.style.left = "50%";
                    alertBox.style.transform = "translateX(-50%)";
                    alertBox.style.background = "#444";
                    alertBox.style.color = "#fff";
                    alertBox.style.padding = "10px 20px";
                    alertBox.style.borderRadius = "5px";
                    alertBox.style.zIndex = "1000";
                    alertBox.innerText = message;
                    
                    document.body.appendChild(alertBox);
            
                    setTimeout(function() {
                        document.body.removeChild(alertBox);
                    }, duration);
                }
               function startWrite() {
                    showTemporaryAlert("AI写作中，请稍候...", 3000); // 显示3秒
                    var keywords = document.getElementById('keywords').value;
                    var messages = JSON.parse(localStorage.getItem('aiConversation') || '[]');
                    messages.push({"role": "user", "content": keywords});
                    var data = JSON.stringify({
                        "model": "{$model}",
                        "messages": messages,
                        "temperature": {$temperature},
                        "max_tokens": {$maxTokens},
                        "stream": true
                    });
                    
                    // Clear the input box after making the request
                    const keywordsTextarea = document.getElementById('keywords');
                    keywordsTextarea.value = '';
                    keywordsTextarea.style.height = '35px'; // 设置初始高度

                    fetch('{$apiUrl}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer {$apiKey}'
                        },
                        body: data
                    }).then(response => {
                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let text = '';
                        reader.read().then(function processText({ done, value }) {
                            if (done) {
                                messages.push({"role": "assistant", "content": text});
                                localStorage.setItem('aiConversation', JSON.stringify(messages));
                                updateResponseArea(messages);
                                return;
                            }
                            const decodedValue = decoder.decode(value, { stream: true });
                            const lines = decodedValue.split("\\n");
                            for (const line of lines) {
                                if (line.startsWith('data: ')) {
                                    const json = line.substring(6);
                                    try {
                                        const parsedData = JSON.parse(json);
                                        if (parsedData.choices && parsedData.choices.length > 0) {
                                            const content = parsedData.choices[0].delta.content || '';
                                            text += content;
                                            document.getElementById("blog_respone").value += content;
                                            document.getElementById("blog_respone").scrollTop = document.getElementById("blog_respone").scrollHeight;
                                        }
                                    } catch (e) {
                                        console.error("Error parsing JSON:", e);
                                    }
                                }
                            }
                            reader.read().then(processText);
                        });
                    }).catch(error => {
                        console.error('Error:', error);
                    });

               }
               function updateResponseArea(messages) {
                    const responseArea = document.getElementById("blog_respone");
                    responseArea.value = '';
                    messages.forEach((message, index) => {
                        responseArea.value += (message.role === 'user' ? 'User' : 'Assistant') + ': ' + message.content + '\\n';
                    });
                    responseArea.scrollTop = responseArea.scrollHeight;
               }
               
            </script>
            <div style="margin:0px 0px 10px 0px;text-align:left;">
                <a class="primary" id="aiWrite-button" style = "text-decoration:none; color:white; padding:7px; margin:17px 0px 17px 0px"onclick="showAiContent()">展开AI写作</a>
            </div>
            <div id="AiWriteAsk" style="display:none;margin-top:10px;margin-bottom:10px;">
                <div style="display: flex; align-items: center;">
                    <textarea class="w-60 text title" id="keywords"></textarea>
                    <label onclick="startWrite()" class="primary" style="text-decoration:none; color:white; padding:7px 15px; margin:17px 5px">开始生成</label>
                    <label onclick="copyContent()" class="primary" style="text-decoration:none; color:white; padding:7px 15px; margin:17px 5px">复制内容</label>
                    <label onclick="clearConversation()" class="primary" style="text-decoration:none; color:white; padding:7px 15px; margin:17px 5px">清空聊天记录</label>
                </div>
            </div>

            <div id="blog_respone_div" style="display:none;">
                <textarea autocomplete="off" id="blog_respone" class="w-100 mono" rows="5">
                </textarea>
            </div>
EOF;
    }
    
    /**
     * 输出头部css
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function header() {
    }
    
    /**
     * 输出底部js
     * 
     * @access public
     * @param unknown $header
     * @return unknown
     */
    public static function footer() {
        
    }
}
?>
