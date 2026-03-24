/*
 * isim  : jQuery KalanKarakter eklentisi
 * sürüm : v2.1  (21 Haziran 2010)
 * adres : http://www.eburhan.com/araclar/
 * yazar : Erhan BURHAN (eburhan)
 *
 * Telif Hakkı (c) 2010 eburhan
 * MIT ve GPL lisansları altında kullanılabilir
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */

(function($) {

    $.fn.kalanKarakter = function(opt){

        // varsayılan seçenekler
        def = {
         limit: 250,
         mesaj: 'kalan karakter: #1',
         kapsa: '<div></div>',
         uyari: function(){}
        };

        // varsayılan seçenekleri, kullanıcının seçenekleri ile değiştir
        var Ayar = $.extend({}, def, opt); 

        // herbir nesne için işlemleri uygula
        this.each(function(i, txtArea){
            var txtAreaId = $(txtArea).attr('id');
            var kapsaTip  = typeof Ayar.kapsa;
            var kapsaObj  = (kapsaTip == 'object') ? Ayar.kapsa : $(Ayar.kapsa);
            var kapsaId   = kapsaObj.attr('id');

            // ID'si olmayanlar varsa ID üret ( JKK = jQuery Kalan Karakter)
            if(! txtArea) {
                txtAreaId = 'text_' + (Math.random() * Math.pow(10,18));
                $(txtArea).attr('id', txtAreaId);
            }

            // başlar başlamaz karakter durumunu kontrol et
            kalanKarakterKontrol(txtArea);

            // Geçerli nesnede kalan karakter sayısı
            var KKS = kalanKarakterSayisi(txtArea);

            // Kalan karakter sayısının gösterildiği yeri ayarla
            if(! kapsaId) {
                kapsaId = 'JKK_' + txtAreaId;
                kapsaObj.attr(kapsaId);
            }

            // Yer yoksa text alanından hemen sonra yeni bir yer aç
            if(kapsaTip == 'string')
                $(txtArea).after(kapsaObj);

            // Kalan karakter sayısını yerinde göster
            kapsaObj.attr('id', kapsaId).html( Ayar.mesaj.replace(/#1/, KKS) );
            $(txtArea).keyup(function(){ kalanKarakterYazdir(kapsaId, txtArea) })
        });

        // Text alanında limiti aşan karakterleri temizle
        function kalanKarakterKontrol(obj) {
            $(obj).val( $(obj).val().substr(0, Ayar.limit) );
        }

        // Text alanında kalan karakter sayısını hesapla
        function kalanKarakterSayisi(obj) {
            return Ayar.limit - $(obj).val().length;
        }

        // Text alanının altına, kalan karakter sayısını yazdır
        function kalanKarakterYazdir(kapsaID, obj) {
            var KKS = kalanKarakterSayisi(obj);

            if( KKS < 0) {
                kalanKarakterKontrol(obj);
                KKS = 0;
                Ayar.uyari();
            }

            $('#'+kapsaID).html(Ayar.mesaj.replace(/#1/, KKS));
        }

        // zincirleme için
        return this;

    }// eklenti sonu

})(jQuery);