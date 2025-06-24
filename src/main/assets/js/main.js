jQuery(document).ready(function($) {
    // スクリプトが読み込まれたことをコンソールに出力
    console.log('Event Popup Viewer script loaded.');

    $(document).on('click', '.epv-event-link', function(e) {
        e.preventDefault();

        // クリックが反応したことをコンソールに出力
        console.log('Event link clicked!');

        // データを取得
        const title = $(this).data('title');
        const date = $(this).data('date');
        const content = $(this).data('content');
        const mapEmbed = $(this).data('map-embed');

        // ポップアップにデータを設定
        $('#epv-popup-title').text(title);
        $('#epv-popup-date').html('<strong>開催日:</strong> ' + date);
        $('#epv-popup-content').html(content.replace(/\n/g, '<br>'));

        // 地図の処理
        if (mapEmbed) {
            try {
                const decodedMapHtml = atob(mapEmbed);
                $('#epv-popup-map').html(decodedMapHtml).show();
            } catch(e) {
                $('#epv-popup-map').hide();
            }
        } else {
             $('#epv-popup-map').hide().html('');
        }

        // ポップアップを表示
        $('#epv-popup-overlay').fadeIn();
        $('body').addClass('epv-popup-open');
    });

    function closeEpvPopup() {
        $('#epv-popup-overlay').fadeOut();
        $('body').removeClass('epv-popup-open');
    }

    $(document).on('click', '#epv-popup-close, #epv-popup-overlay', function(e) {
        if (e.target.id === 'epv-popup-wrap' || $(e.target).closest('#epv-popup-wrap').length) {
            if(e.target.id !== 'epv-popup-close' && !$(e.target).closest('#epv-popup-close').length){
                 return;
            }
        }
        closeEpvPopup();
    });

    $(document).on('keydown', function(e) {
        if (e.key === "Escape" && $('body').hasClass('epv-popup-open')) {
            closeEpvPopup();
        }
    });
});