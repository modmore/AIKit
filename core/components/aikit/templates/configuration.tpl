<div class="x-panel-bwrap">
    <div class="x-panel-body xpanel-body-noheader">
        <div class="x-panel container x-panel-noborder">
            <div class="x-panel-body">
                <div class="x-panel-body">
                    <div class="x-panel modx-page-header">
                        <h2>{$_lang.aikit}</h2>
                    </div>

                    <div class="x-tab-panel modx-tabs">
                        <div class="x-tab-panel-header x-unselectable x-tab-panel-header-plain">
                            <div class="x-tab-strip-wrap" >
                                <ul class="x-tab-strip x-tab-strip-top">
                                    <li class="x-tab-strip-active">
                                        <a class="x-tab-strip-close"></a>
                                        <a href="#"><span class="x-tab-strip-text">Assistant Configuration</span></a>
                                    </li>
                                    <li class="x-tab-edge"><span class="x-tab-strip-text">&nbsp;</span></li>
                                    <div class="x-clear"></div>
                                </ul>
                            </div>
                            <div class="x-tab-strip-spacer"></div>
                        </div>
                        <div class="x-tab-panel-bwrap">
                            <div class="x-tab-panel-body x-tab-panel-body-top" style="overflow: auto; height: auto;">
                                <div class="x-panel x-panel-noborder">
                                    <div class="x-panel-bwrap">
                                        <div class="x-panel-body x-panel-body-noheader x-panel-body-noborder"
                                             style="height: auto;">
                                            <div class="x-panel x-panel-noborder">
                                                <div class="x-panel-bwrap">
                                                    <div class="x-panel-body panel-desc x-panel-body-noheader x-panel-body-noborder">
                                                        <p>Your AI Kit Assistant </p>
                                                        <hr>
                                                        <h3>Model Configuration</h3>
                                                        <p>Choose your preferred AI provider and model. Select from OpenAI, Gemini, etc. Dropdown for available models for each provider. For OpenAI specifically make sure there is an endpoint (url) option cause many LLM providers are OpenAI-compatible, including DeepSeek.</p>
                                                        <p style="margin-top: 1em;">
                                                            Using LLM: <b>{$settings['aikit.model']}</b><br>
                                                            Using OpenAI Endpoint: <b>{$settings['aikit.openai_endpoint']}</b><br>
                                                            Using OpenAI Model: <b>{$settings['aikit.openai_model']}</b><br>
                                                        </p>

                                                        {section name=toolIdx loop=$tools}
                                                            <hr>
                                                            <h3>
                                                                {$tools[toolIdx].name}
                                                                <code style="color: #999; font-size: 0.8em; font-family: monospace; padding-left: 1.5em;">{$tools[toolIdx].class}</code>
                                                            </h3>
                                                            <p>{$tools[toolIdx].description}</p>

                                                            <ul>
                                                            {section name=toolParamIdx loop=$tools[toolIdx].parameters}
                                                                <li>
                                                                    {$toolParamIdx}: {$tools[toolIdx].parameters[toolParamIdx].type}  {$tools[toolIdx].parameters[toolParamIdx].description}
                                                                </li>
                                                            {/section}
                                                            </ul>
                                                        {/section}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="x-panel x-panel-noborder x-hide-display">
                                                <div class="x-panel-bwrap">
                                                    <div class="x-panel-body panel-desc error x-panel-body-noheader x-panel-body-noborder">

                                                        <div class="x-panel">
                                                            <div class="x-panel-body">
                                                                <h3>Configuration</h3>
                                                                <p>Hello world.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>