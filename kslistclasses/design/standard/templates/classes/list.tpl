{if $error|not()}
    {def
        $objects = fetch( 'content', 'list', hash(
                'parent_node_id', 2,
                'class_filter_type', 'include',
                'class_filter_array', array( $class_id ),
                'sort_by', array( $sort_by, $order_by ),
                'offset', $offset,
                'limit', $limit,
                'depth', 0,
            ) 
        )
        $last_object      = $offset|sum($limit)
    }

    <div class="context-block">
        <div class="box-header">
            <h1 class="context-title">
                {"%class_name object list (%objects_count)"|i18n(
                        'design/admin/error/classes/list', 
                        'Both %class_name and %%objects_count are set in controller.', 
                        hash(
                            '%class_name', $class_name,
                            '%objects_count', $objects_count,
                        )
                    )
                }
            </h1>
            <div class="header-mainline"></div>
        </div>

        {if $objects_count}
            <div class="box-content">
                <table class="list class-list">
                    <thead>
                        <tr>
                            {foreach $headers as $header}
                                <th class="{$header.class|wash}">
                                    <a href={$header.link|ezurl}>{$header.text}</a>
                                </th>
                            {/foreach}
                            <th class="tight">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $objects as $object sequence array(bglight,bgdark) as $bgColor}
                            <tr class="{$bgColor}">
                                <td>
                                    {$object.class_identifier|class_icon( 'small', 'ico' )}
                                    <a href={$object.object.main_node.url_alias|ezurl()}>{$object.name}</a>
                                    {if $object.is_hidden}<sup>[hidden]<sup>{/if}
                                </td>
                                <td>
                                    {$object.node_id|wash()}
                                </td>
                                <td>
                                    <a href={$object.parent.url_alias|ezurl()}>{$object.parent.name|wash()}</a>
                                </td>
                                <td>
                                    <a href={$object.creator.main_node.url_alias|ezurl()}>{$object.creator.name|wash()}</a>
                                </td>
                                <td>
                                    {$object.object.modified|l10n( shortdatetime )}
                                </td>
                                <td>
                                    <a href={concat( '/content/edit/', $object.node_id )|ezurl()}>
                                        <img width="16" height="16" title="{'Edit node'|i18n( 'design/admin/classes/list' )}" alt="{'Edit'|i18n( 'design/admin/classes/list' )}" src={'edit.gif'|ezimage()} class="button">
                                    </a>
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {/if}

        <div class="block">
            <div class="box-content">
                <div class="block">
                    <div class="left">
                    {if $objects_count|lt($last_object)}
                        {set $last_object = $objects_count}
                    {/if}
                    {def $from = $offset|sum(1)
                         $to   = $from|sum($limit)
                    }
                    {if $to|ge($last_object)}
                        {set $to = $last_object}
                    {/if}
                    <p>Records from {$from} to {$to}</p>
                    </div>
                    <div class="break"></div>
                    <div class="object-center">
                        <p class="text-center ">
                            {foreach $pagination as $page}
                                {if $page.disabled}
                                    <span class="disabled">{$page.text}</span>
                                {else}
                                    {def $link = concat("/classes/list/id/", $class_id, "/page/", $page.link, "/sort/", $sort_by, "/order/", $order_by)}
                                    <a href={$link|ezurl} title="{$page.text}">[{$page.text}]</a>
                                {/if}
                            {/foreach}
                        </p>
                    </div>
                    <div class="break"></div>
                </div>
            </div>
        </div>
    </div>

{else}
    <div class="message-error">
        <h2>
            <span class="time">[{currentdate()|l10n( shortdatetime )}]</span>
            {$error|i18n( 'design/admin/error/classes/list' )}
        </h2>
    </div>
{/if}