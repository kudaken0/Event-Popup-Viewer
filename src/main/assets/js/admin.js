jQuery(document).ready(function($){
    // カラーピッカーの初期化
    if ($('.epv-color-picker').length) {
        $('.epv-color-picker').wpColorPicker();
    }

    // 都道府県「その他」の入力欄の表示/非表示制御
    var prefSelect = $('#epv_event_prefecture');
    
    // prefSelect要素が存在する場合のみ、以下の処理を実行
    if (prefSelect.length) {
        var prefOtherWrap = $('#epv_event_prefecture_other_wrap');

        // 表示/非表示を切り替える関数
        function toggleOtherInput() {
            if (prefSelect.val() === 'その他') {
                prefOtherWrap.show();
            } else {
                prefOtherWrap.hide();
            }
        }

        // ページ読み込み時に実行
        toggleOtherInput();

        // ドロップダウンの選択が変更されたときに実行
        prefSelect.on('change', toggleOtherInput);
    }
});