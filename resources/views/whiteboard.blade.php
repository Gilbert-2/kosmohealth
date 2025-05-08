<!DOCTYPE html>
<html lang="{{config('config.system.locale') ?? 'en'}}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0, user-scalable=no">

    <title>{{ trans('whiteboard.whiteboard') }}</title>
    <meta name="author" content="{{ config('config.basic.seo_author') ?? 'KodeMint' }}">

    <link rel="shortcut icon" href="{{ config('config.assets.favicon') }}">

    <style>
        textarea::::-webkit-scrollbar {
            width: 0;
            height: 0;
        }
        textarea::-webkit-scrollbar {
            height: 0;
            overflow: visible;
            width: 10px;
            border-left: 1px solid rgb(229, 229, 229);
        }
        textarea::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, .2);
            background-clip: padding-box;
            min-height: 28px;
            padding: 100px 0 0;
            box-shadow: inset 1px 1px 0 rgba(0, 0, 0, .1), inset 0 -1px 0 rgba(0, 0, 0, .07);
            border-width: 1px 1px 1px 6px;
        }
        textarea::-webkit-scrollbar-button {
            height: 0;
            width: 0;
        }
        textarea::-webkit-scrollbar-track {
            background-clip: padding-box;
            border: solid transparent;
            border-width: 0 0 0 4px;
        }
        textarea::-webkit-scrollbar-corner {
            background: transparent;
        }
        ::selection,
        ::-moz-selection {
            background: #00A2E8;
            color: #fff;
            text-shadow: none;
        }
        html,
        body,
        input,
        textarea,
        h1,
        h2 {
            margin: 0;
            padding: 0;
            word-wrap: break-word;
            -o-text-overflow: ellipsis;
            -ms-text-overflow: ellipsis;
            text-overflow: ellipsis;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            font-family: Verdana, arial, helvetica, sans-serif;
        }
        html,
        body {
            background-color: #FFFFFF;
            font-size: 13px;
            height: 100%;
            cursor: default;
            color: #212335;
            overflow: hidden;
        }
        /* header */

        h1,
        .line-width-done,
        .colors-done,
        .additional-close {
            background: -moz-linear-gradient(top, rgba(255, 255, 255, 1) 0%, rgba(243, 243, 243, 1) 97%, rgba(237, 237, 237, 1) 97%, rgba(255, 255, 255, 1) 100%);
            /* FF3.6+ */

            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, rgba(255, 255, 255, 1)), color-stop(97%, rgba(243, 243, 243, 1)), color-stop(97%, rgba(237, 237, 237, 1)), color-stop(100%, rgba(255, 255, 255, 1)));
            /* Chrome,Safari4+ */

            background: -webkit-linear-gradient(top, rgba(255, 255, 255, 1) 0%, rgba(243, 243, 243, 1) 97%, rgba(237, 237, 237, 1) 97%, rgba(255, 255, 255, 1) 100%);
            /* Chrome10+,Safari5.1+ */

            background: -o-linear-gradient(top, rgba(255, 255, 255, 1) 0%, rgba(243, 243, 243, 1) 97%, rgba(237, 237, 237, 1) 97%, rgba(255, 255, 255, 1) 100%);
            /* Opera 11.10+ */

            background: -ms-linear-gradient(top, rgba(255, 255, 255, 1) 0%, rgba(243, 243, 243, 1) 97%, rgba(237, 237, 237, 1) 97%, rgba(255, 255, 255, 1) 100%);
            /* IE10+ */

            background: linear-gradient(top, rgba(255, 255, 255, 1) 0%, rgba(243, 243, 243, 1) 97%, rgba(237, 237, 237, 1) 97%, rgba(255, 255, 255, 1) 100%);
            /* W3C */

            filter: progid: DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ffffff', GradientType=0);
            /* IE6-9 */
        }
        /* links */

        a {
            color: #0b7aff;
            text-decoration: none;
        }
        a:hover {
            color: #043877;
        }
        /* design-surface */

        .design-surface {
            background-color: transparent;
        }
        .design-surface canvas {
            position: absolute;
        }
        .design-surface canvas:last-child {
            position: static;
        }
        /* toolbox */

        .tool-box {
            border-right: 1px solid #e5e6e8;
            position: absolute;
            top: 0;
            width: 40px;
            background-color: #fbfcfe;
            overflow: hidden;
            height: 100%;
            overflow: hidden;
            overflow-y: auto;
        }

        .tool-box::-webkit-scrollbar {
            width: 0;
            height: 0;
            background-color:transparent;
        }

        .tool-box::-webkit-scrollbar-button {
            height: 0;
            width: 0
        }

        .tool-box::-webkit-scrollbar-thumb {
            background-color:transparent;
            border: 0;
        }


        .tool-box::-webkit-scrollbar-track {
            background-color:transparent;
            box-shadow: none;
        }

        .tool-box canvas {
            border-bottom: 1px solid #e5e6e8;
            /* margin-top: -4px; */
            display: none;
            /* margin: 2px; */
            cursor: pointer;
        }
        .tool-box canvas:first-child,
        .tool-box canvas:first-child:hover {
            margin-top: 0;
            border-top: 0;
        }
        .tool-box canvas:hover {
            box-shadow: none;
            background: #ebecee;
        }
        .selected-shape {
            background: #e1e2e4!important;
        }
        /* surface */

        .preview-panel {
            position: absolute;
            right: 10px;
            background-color: #fbfcfe;
            bottom: 0;
        }
        .preview-panel div {
            border: 1px solid #e5e6e8;
            display: inline-block;
            padding: 5px 10px;
        }
        .preview-panel #code-preview {
            margin-left: -6px;
        }
        .preview-panel div {
            border: 1px solid #e5e6e8;
        }
        .preview-panel div:not(.preview-selected) {} .preview-panel div {
            border: 1px solid rgb(9, 159, 243);
        }
        .preview-selected {
            margin-top: -4px;
            background-color: rgb(6, 205, 255);
            color: white;
        }
        /* input */

        input,
        select {
            border: 1px solid #dee2e9;
            border-radius: 0.2rem;
            -webkit-user-select: initial;
            -moz-user-select: initial;
            -ms-user-select: initial;
            user-select: initial;
            outline: none;
        }
        input:focus {
            border-color: #bfc7d5;
        }
        /* select */

        .allow-select {
            -webkit-user-select: initial;
            -moz-user-select: initial;
            user-select: initial
        }
        /* arc */

        .arc-range-container, #pdf-page-container {
            position: absolute;
            z-index: 1000;
            display: none;
        }
        .arc-range {
            font-size: 18px;
            padding: 2px 16px;
            text-align: center;
            width: 50px;
        }
        .arc-range-container-guide {
            margin-top: 10px;
            color: #C5C6C8;
        }
        /* code viewer */

        .code-container {
            position: absolute;
            top: 0;
            width: 100%;
            display: none;
        }
        .code-text {
            resize: none;
            width: 99%;
            height: 300px;
            padding: 15px;
            outline: 0;
            border: 0;
            border-top: 1px solid #e5e6e8;
            font-family: Consolas, "Andale Mono", "Lucida Console", "Courier New", monospace;
            color: #666;
        }
        /* options */

        .options-container {
            position: absolute;
            top: 0;
            left: 0;
            border: 1px solid #e5e6e8;
            border-left: 0;
            background-color: #fbfcfe;
            display: none;
        }
        .options-container div {
            border-bottom: 1px solid #e5e6e8;
            padding: 5px;
        }
        .options-container div:last-child {
            border-bottom: 0;
        }
        /* context menu */

        .context-popup {
            position: absolute;
            display: none;
            padding: 10px;
            border: 1px solid #e5e6e8;
            background-color: #fbfcfe;
            box-shadow: inset 0 0 14px rgb(229, 229, 229);
            border-radius: 8px;
        }
        .line-width-text,
        .colors-container input {
            width: 25px;
            padding: 2px 5px;
            text-align: center;
        }
        /* colors */

        .colors-container label {
            width: 100px;
            display: inline-block;
        }
        .colors-container input {
            width: 100px;
            text-align: left;
        }
        .colors-container .input-div {
            margin-bottom: 5px;
        }
        /* additional controls */

        .additional-container label,
        .additional-container select {
            width: 200px;
            display: inline-block;
        }
        .additional-container select {
            width: 120px;
        }
        .btn-007 {
            font-family: Verdana, arial, helvetica, sans-serif;
            font-weight: normal;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #212532;
            display: block;
            text-shadow: none;
            background: #dee2e9;
            font-size: 13px;
            border: 1px solid #dee2e9;
            outline: none;
            margin-top: 10px;
            cursor: pointer;
            text-align: center;
            text-transform: uppercase;
        }
        .btn-007:hover,
        .btn-007:focus {
            color: #212532;
            background-color: #c7ceda;
            border-color: #bfc7d5;
        }
        .btn-007[disabled] {
            background: rgb(249, 249, 249);
            border: 1px solid rgb(218, 207, 207);
            color: rgb(197, 189, 189);
        }
        .fontSelect {
            position: relative;
            padding: 3px;
            height: 28px;
            line-height: 28px;
            cursor: pointer;
            margin: 3px;
            width: 200px;
            background-image: -webkit-linear-gradient(top, #f8f9fb, #f0f1f3);
            background-image: -moz-linear-gradient(top, #f8f9fb, #f0f1f3);
            background-image: -o-linear-gradient(top, #f8f9fb, #f0f1f3);
            background-image: -ms-linear-gradient(top, #f8f9fb, #f0f1f3);
            background-image: linear-gradient(top, #f8f9fb, #f0f1f3);
            filter: progid: DXImageTransform.Microsoft.gradient(GradientType=0, StartColorStr='$from', EndColorStr='$to');
            -webkit-border-radius: 4px;
            -moz-border-radius: 4px;
            border-radius: 4px;
            border: 1px solid #cccdcf;
        }
        .fontSelect span {
            overflow: hidden;
            margin-left: 5px;
        }
        .fontSelect .arrow-down {
            position: absolute;
            right: 10px;
            top: 14px;
            border-left: 6px solid transparent;
            border-right: 6px solid transparent;
            border-top: 6px solid #AAA;
        }
        .fontSelectUl,
        .fontSizeUl {
            list-style: none;
            width: 200px;
            background: #f8f9fb;
            position: absolute;
            left: 0;
            top: 35px;
            padding: 0;
        }
        .fontSelectUl {
            padding-left: 5px;
        }
        .fontSelectUl li,
        .fontSizeUl li {
            height: 24px;
            line-height: 24px;
            overflow: hidden;
            cursor: pointer;
            padding: 0 10px;
            font-size: 14px;
            border-left: 1px solid #f0f1f3;
            border-right: 1px solid #f0f1f3;
        }
        .fontSelectUl li:last-child,
        .fontSizeUl li:last-child {
            -webkit-border-radius: 0 0 4px 4px;
            -moz-border-radius: 0 0 4px 4px;
            border-radius: 0 0 4px 4px;
            border-bottom: 1px solid #ecedef;
        }
        .fontSelectUl li:hover,
        .fontSizeUl li:hover {
            background: #dcdddf;
        }
        .fontSelectUl li.font-family-selected,
        .fontSizeUl li.font-size-selected {
            background: rgb(6, 205, 255)!important;
            color: white!important;
        }
        #marker-selected-color,
        #marker-selected-color-2, #pencil-selected-color,
        #pencil-selected-color-2 {
            display: inline-block;
            vertical-align: middle;
            width: 30px;
            height: 30px;
        }
        #marker-fill-colors, #pencil-fill-colors {
            display: none;
        }
        #marker-color-container, #pencil-color-container {
            position: relative;
        }
        #marker-fill-colors, #pencil-fill-colors {
            left: 100%;
            top: 0;
            width: 100px;
            padding: 10px;
        }
        #marker-fill-style, #pencil-fill-style {
            width: 50px;
            display: inline-block;
        }
        #marker-colors-list, #pencil-colors-list {
            margin: 10px auto;
            border-collapse: collapse;
        }
        #marker-colors-list td, #pencil-colors-list td {
            width: 15px;
            height: 15px;
            border: 1px solid black;
            padding: 0;
        }
        #marker-colors-list td:hover, #pencil-colors-list td:hover {
            border: 1px solid white;
        }

        #pdf-next, #pdf-prev, #pdf-close {
            width: 22px;
            vertical-align: middle;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <section class="design-surface">
        <canvas id="temp-canvas"></canvas>
        <canvas id="main-canvas"></canvas>
    </section>

    <!-- toolbox -->
    <section id="tool-box" class="tool-box">
        <canvas id="pencil-icon" width="40" height="40" title="{{ trans('whiteboard.tools.pencil') }}"></canvas>
        <canvas id="marker-icon" width="40" height="40" title="{{ trans('whiteboard.tools.marker') }}"></canvas>

        <canvas id="eraser-icon" width="40" height="40" title="{{ trans('whiteboard.tools.eraser') }}"></canvas>
        <canvas id="text-icon" width="40" height="40" title="{{ trans('whiteboard.tools.write_text') }}"></canvas>
        <canvas id="image-icon" width="40" height="40" title="{{ trans('whiteboard.tools.add_image') }}"></canvas>

        <canvas id="pdf-icon" width="40" height="40" title="{{ trans('whiteboard.tools.add_pdf') }}"></canvas>

        <canvas id="drag-last-path" width="40" height="40" title="{{ trans('whiteboard.tools.drag_move_last_path') }}"></canvas>
        <canvas id="drag-all-paths" width="40" height="40" title="{{ trans('whiteboard.tools.drag_move_all_paths') }}"></canvas>

        <canvas id="line" width="40" height="40" title="{{ trans('whiteboard.tools.draw_lines') }}"></canvas>
        <canvas id="arrow" width="40" height="40" title="{{ trans('whiteboard.tools.draw_arrows') }}"></canvas>

        <canvas id="zoom-up" width="40" height="40" title="{{ trans('whiteboard.tools.zoon_in') }}"></canvas>
        <canvas id="zoom-down" width="40" height="40" title="{{ trans('whiteboard.tools.zoom_out') }}"></canvas>

        <canvas id="arc" width="40" height="40" title="{{ trans('whiteboard.tools.arc') }}"></canvas>
        <canvas id="rectangle" width="40" height="40" title="{{ trans('whiteboard.tools.rectangle') }}"></canvas>
        <canvas id="quadratic-curve" width="40" height="40" title="{{ trans('whiteboard.tools.quadratic_curve') }}"></canvas>
        <canvas id="bezier-curve" width="40" height="40" title="{{ trans('whiteboard.tools.bezier_curve') }}"></canvas>

        <canvas id="undo" width="40" height="40" title="{{ trans('whiteboard.tools.undo_remove_recent_shapes') }}"></canvas>

        <canvas id="line-width" width="40" height="40" title="{{ trans('whiteboard.tools.set_line_width') }}"></canvas>
        <canvas id="colors" width="40" height="40" title="{{ trans('whiteboard.tools.set_foreground_and_background_colors') }}"></canvas>
        <canvas id="additional" width="40" height="40" title="{{ trans('whiteboard.tools.extra_options') }}"></canvas>
    </section>

    <!-- pdf -->
    <section id="pdf-page-container">
        <img id="pdf-prev" />
        <select id="pdf-pages-list"></select>
        <img id="pdf-next" />
        <img id="pdf-close" />
    </section>

    <!-- arc -->
    <section id="arc-range-container" class="arc-range-container">
        <input id="arc-range" class="arc-range" type="text" value="2">
        <input type="checkbox" id="is-clockwise" checked="" class="allow-select">
        <label for="is-clockwise">{{ trans('whiteboard.clockwise') }}?</label>
        <div class="arc-range-container-guide">{{ trans('whiteboard.use_arrow_keys') }} ↑↓</div>
    </section>

    <!-- generated code -->
    <section class="code-container">
        <textarea id="code-text" class="code-text allow-select"></textarea>
    </section>

    <section class="preview-panel" style="display: none;">
        <div id="design-preview" class="preview-selected">{{ trans('whiteboard.preview') }}</div>
        <div id="code-preview">{{ trans('whiteboard.code') }}</div>
    </section>

    <!-- options -->
    <section id="options-container" class="options-container">
        <div>
            <input type="checkbox" id="is-absolute-points" checked="">
            <label for="is-absolute-points">{{ trans('whiteboard.absolute_points') }}</label>
        </div>
        <div>
            <input type="checkbox" id="is-shorten-code" checked="">
            <label for="is-shorten-code">{{ trans('whiteboard.shorten_code') }}</label>
        </div>
    </section>

    <!-- line-width -->
    <section id="line-width-container" class="context-popup">
        <label for="line-width-text">{{ trans('whiteboard.line_width') }}:</label>
        <input id="line-width-text" class="line-width-text" type="text" value="2">

        <div id="line-width-done" class="btn-007">{{ trans('whiteboard.done') }}</div>
    </section>

    <!-- colors selector -->
    <section id="colors-container" class="context-popup colors-container">
        <div class="input-div">
            <label for="stroke-style">{{ trans('whiteboard.stroke_style') }}:</label>
            <input id="stroke-style" type="color" value="#5710a3">
        </div>

        <div class="input-div">
            <label for="fill-style">{{ trans('whiteboard.fill_style') }}:</label>
            <input id="fill-style" type="color" value="#ffffff">
        </div>

        <div id="colors-done" class="btn-007">{{ trans('whiteboard.done') }}</div>
    </section>

    <!-- marker selector -->
    <section id="marker-container" class="context-popup colors-container">

        <div class="input-div">
            <label for="marker-stroke-style">{{ trans('whiteboard.thickness') }}:</label>
            <select id="marker-stroke-style" value="20">
                <option value='8'>8</option>
                <option value='9'>9</option>
                <option value='10'>10</option>
                <option value='11'>11</option>
                <option value='12'>12</option>
                <option value='14'>14</option>
                <option value='16'>16</option>
                <option value='18'>18</option>
                <option value='20' selected>20</option>
                <option value='22'>22</option>
                <option value='22'>22</option>
                <option value='24'>24</option>
                <option value='26'>26</option>
                <option value='28'>28</option>
                <option value='36'>36</option>
                <option value='36'>36</option>
                <option value='48'>48</option>
                <option value='72'>72</option>
            </select>
        </div>
        <div class="input-div" id='marker-color-container'>
            <label for="marker-fill-style">{{ trans('whiteboard.fill_color') }}:</label>
            <div id="marker-selected-color"></div>
            <div id="marker-fill-colors" class='context-popup'>
                <div class="top">
                    <div id="marker-selected-color-2"></div>
                    <input id="marker-fill-style" type="text" value="ea628d">
                </div>
                <table id="marker-colors-list">

                </table>
            </div>
        </div>
        </div>

        <div id="marker-done" class="btn-007">{{ trans('whiteboard.done') }}</div>
    </section>

    <!-- pencil selector -->
    <section id="pencil-container" class="context-popup colors-container">

        <div class="input-div">
            <label for="pencil-stroke-style">{{ trans('whiteboard.thickness') }}:</label>
            <select id="pencil-stroke-style">
                <option value='1'>1</option>
                <option value='2'>2</option>
                <option value='3' selected>3</option>
                <option value='4'>4</option>
                <option value='5'>5</option>
                <option value='6'>6</option>
                <option value='7'>7</option>
                <option value='8'>8</option>
                <option value='9'>9</option>
                <option value='10'>10</option>
                <option value='11'>11</option>
                <option value='12'>12</option>
                <option value='14'>14</option>
                <option value='16'>16</option>
                <option value='18'>18</option>
                <option value='20'>20</option>
                <option value='22'>22</option>
                <option value='22'>22</option>
                <option value='24'>24</option>
                <option value='26'>26</option>
                <option value='28'>28</option>
                <option value='36'>36</option>
                <option value='36'>36</option>
                <option value='48'>48</option>
                <option value='72'>72</option>
            </select>
        </div>
        <div class="input-div" id='pencil-color-container'>
            <label for="pencil-fill-style">{{ trans('whiteboard.fill_color') }}:</label>
            <div id="pencil-selected-color"></div>
            <div id="pencil-fill-colors" class='context-popup'>
                <div class="top">
                    <div id="pencil-selected-color-2"></div>
                    <input id="pencil-fill-style" type="text" value="320F57">
                </div>
                <table id="pencil-colors-list">

                </table>
            </div>
        </div>
        </div>

        <div id="pencil-done" class="btn-007">{{ trans('whiteboard.done') }}</div>
    </section>

    <!-- copy paths -->
    <section id="copy-container" class="context-popup">
        <div>
            <input type="checkbox" id="copy-last" checked="" />
            <label for="copy-last">{{ trans('whiteboard.copy_last_path') }}</label>
        </div>
        <div style="margin-top: 5px;">
            <input type="checkbox" id="copy-all" />
            <label for="copy-all">{{ trans('whiteboard.copy_all_paths') }}</label>
        </div>
    </section>

    <!-- additional controls -->
    <section id="additional-container" class="context-popup additional-container">
        <div>
            <label for="lineCap-select">{{ trans('whiteboard.line_cap') }}:</label>
            <select id="lineCap-select">
                <option>{{ trans('whiteboard.round') }}</option>
                <option>{{ trans('whiteboard.butt') }}</option>
                <option>{{ trans('whiteboard.square') }}</option>
            </select>
        </div>

        <div>
            <label for="lineJoin-select">{{ trans('whiteboard.line_join') }}:</label>
            <select id="lineJoin-select">
                <option>{{ trans('whiteboard.round') }}</option>
                <option>{{ trans('whiteboard.bevel') }}</option>
                <option>{{ trans('whiteboard.miter') }}</option>
            </select>
        </div>

        <div>
            <label for="globalAlpha-select">{{ trans('whiteboard.global_alpha') }}:</label>
            <select id="globalAlpha-select">
                <option>1.0</option>
                <option>0.9</option>
                <option>0.8</option>
                <option>0.7</option>
                <option>0.6</option>
                <option>0.5</option>
                <option>0.4</option>
                <option>0.3</option>
                <option>0.2</option>
                <option>0.1</option>
                <option>0.0</option>
            </select>
        </div>

        <div>
            <label for="globalCompositeOperation-select">{{ trans('whiteboard.global_composite_operation') }}:</label>
            <select id="globalCompositeOperation-select">
                <option>{{ trans('whiteboard.source_atop') }}</option>
                <option>{{ trans('whiteboard.source_in') }}</option>
                <option>{{ trans('whiteboard.source_out') }}</option>
                <option selected="">{{ trans('whiteboard.source_over') }}</option>
                <option>{{ trans('whiteboard.destination_atop') }}</option>
                <option>{{ trans('whiteboard.destination_in') }}</option>
                <option>{{ trans('whiteboard.destination_out') }}</option>
                <option>{{ trans('whiteboard.destination_over') }}</option>
                <option>{{ trans('whiteboard.lighter') }}</option>
                <option>{{ trans('whiteboard.copy') }}</option>
                <option>{{ trans('whiteboard.xor') }}</option>
            </select>
        </div>

        <div id="additional-close" class="btn-007">{{ trans('whiteboard.done') }}</div>
    </section>

    <!-- fade -->
    <div id="fade"></div>

    <!-- text font/family/color -->
    <ul class="fontSelectUl" style="display: none; position: absolute; top: 0; left: 0; width: 166px;">
        <li>{{ trans('whiteboard.fonts.arial') }}</li>
        <li>{{ trans('whiteboard.fonts.arial_black') }}</li>
        <li>{{ trans('whiteboard.fonts.comic_sans_ms') }}</li>
        <li>{{ trans('whiteboard.fonts.courier_new') }}</li>
        <li>{{ trans('whiteboard.fonts.georgia') }}</li>
        <li>{{ trans('whiteboard.fonts.tahoma') }}</li>
        <li>{{ trans('whiteboard.fonts.times_new_roman') }}</li>
        <li>{{ trans('whiteboard.fonts.trebuchet_ms') }}</li>
        <li>{{ trans('whiteboard.fonts.verdana') }}</li>
    </ul>

    <ul class="fontSizeUl" style="display: none; position: absolute; top: 0; left: 0; width: 50px; text-align: center;">
        <li>15</li>
        <li>17</li>
        <li>19</li>
        <li>20</li>
        <li>22</li>
        <li>25</li>
        <li>30</li>
        <li>35</li>
        <li>42</li>
        <li>48</li>
        <li>60</li>
        <li>72</li>
    </ul>

    <!-- url-parameters -->
    <script>
        (function() {
            var params = {},
                r = /([^&=]+)=?([^&]*)/g;

            function d(s) {
                return decodeURIComponent(s.replace(/\+/g, ' '));
            }

            var match, search = window.location.search;
            while (match = r.exec(search.substring(1)))
                params[d(match[1])] = d(match[2]);

            window.params = params;
        })();

        window.selectedIcon = params.selectedIcon;
        window.uid = params.uid;

        if (window.selectedIcon) {
            try {
                window.selectedIcon = window.selectedIcon.split('')[0].toUpperCase() + window.selectedIcon.replace(window.selectedIcon.split('').shift(1), '');
            } catch (e) {
                window.selectedIcon = 'Pencil';
            }
        } else {
            window.selectedIcon = 'Pencil';
        }

        var script = document.createElement('script');
        script.src = params.widgetJsURL || '/js/canvas-designer/widget.js';
        (document.body || document.documentElement).appendChild(script);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.0.489/build/pdf.min.js"></script>
</body>

</html>
