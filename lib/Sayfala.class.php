<?php
class Sayfala {
	public $sonuc;
    public $bas;
    public $sonuncu;
    public $v;
    public $slimit;
    public $n_p_link;
    public $a_link;
    public $link;
	public $toplam;
	public $sayfa;
	public $limit;

	//<a href="index.php?cat='.$sef.'&sayfa=#page_num#" '.$ext.' title="#name#">#name#</a>
	public function ileri() {
		$this->sonuc ['ileri'] = $this->sayfa != $this->v ? $this->replace ( $this->i_link, $this->sayfa + 1, $this->i_text ) : $this->replace ( $this->i_p_link, $this->i_text );
	}
	public function geri() {
		$this->sonuc ['geri'] = $this->sayfa != 1 ? $this->replace ( $this->g_link, $this->sayfa - 1, $this->g_text ) : $this->replace ( $this->g_p_link, $this->g_text );
	}

	public function sayfa() {

		$this->bas = $this->sayfa - $this->slimit;
		$this->sonuncu = $this->sayfa + $this->slimit;
		if ($this->bas < 1) {
			$this->bas = 1;
		}
		if ($this->sonuncu > $this->v) {
			$this->sonuncu = $this->v;
		}
		$koberz="";
		if ($this->sayfa - $this->slimit >= 2) {
			$koberz .= $this->replace ( $this->link, 1 );

		}
		if ($this->sayfa - $this->slimit >= 3) {
			$koberz .= $this->replace ( $this->link, 2 );
		}

		if ($this->sayfa - $this->slimit > 3) {
			$koberz .= $this->replace ( $this->n_p_link, '...' );
		}

		for($i = $this->bas; $i <= $this->sonuncu; $i ++) {
			$koberz .= ($this->sayfa != $i) ? $this->replace ( $this->link, $i ) : $this->replace ( $this->a_link, $i );
		}

		if($this->sayfa + $this->slimit <= $this->v-3 ){
			$koberz .= $this->replace ( $this->n_p_link, '...' );
		}

		if($this->sayfa + $this->slimit <= $this->v-2 ){
			$koberz .= $this->replace ( $this->link, $this->v - 1 );
		}

		if($this->sayfa + $this->slimit <= $this->v-1 ){
			$koberz .= $this->replace ( $this->link, $this->v );
		}
		$this->sonuc ['sayfalar'] = $koberz;

	}

	function replace($link, $sayi, $text = NULL) {

		$link = str_replace ( '#page_num#', $sayi, $link );
		return $text == NULL ? str_replace ( '#name#', $sayi, $link ) : str_replace ( '#name#', $text, $link );

	}

	public function run() {
		$this->v = ceil ( $this->toplam / $this->limit );
		$this->sayfa = $this->sayfa > $this->v ? '1' : $this->sayfa;
		$this->ileri ();
		$this->geri ();
		$this->sayfa ();
		if ($this->v <= 1) {
			unset ( $this->sonuc );
		}

		//print_r($this->sonuc);
	}

}
/*
		$this->p = Load::lib ( 'Sayfala' );
		$this->p->link = ' <a href="pictures/listele/#page_num#"  title="#name#">#name#</a> ';
		$this->p->toplam = 300; // Toplam Veri Sayısı
		$this->p->limit = 10; // Sayfa Başına Gösterilecek Veri
		$this->p->slimit = 3; // Sayfalama Limiti
		$this->p->sayfa = $this->url->parca ( '2' ) ? $this->url->parca ( '2' ) : '1';// Geçerli Sayfa
		$this->p->i_text = 'İleri';
		$this->p->g_text = 'Geri';
		$this->p->run (); // Oluştur
		$this->tema->veri ( 'sayfa', $this->p->sonuc );
		$this->tema->veri ( 'tpl', 'pictures/listele.tpl' );
<<style>
<!--
div.sayfa {
	padding: 3px;
	margin: 3px;
}

div.sayfa a {
	padding: 2px 5px 2px 5px;
	margin: 2px;
	border: 1px solid #AAAADD;
	zoom: 100%;
	text-decoration: none;
	color: #000099;
}
div.sayfa a:hover, div.sayfa a:active {
	border: 1px solid #000099;

	color: #000;
}
div.sayfa span.aktif {
	padding: 2px 5px 2px 5px;
	margin: 2px;
	border: 1px solid #000099;

	* zoom: 100%;

	font-weight: bold;
	background-color: #000099;
	color: #FFF;
}
div.sayfa span.pasif {
	padding: 2px 5px 2px 5px;
	margin: 2px;
	border: 1px solid #EEE;

	* zoom: 100%;

	color: #DDD;
}

* span.elipsis {zoom:100%}
-->
</style>

	<div class="sayfa">
		{{$sayfa.geri}} {{$sayfa.sayfalar}} {{$sayfa.ileri}}
	</div>
*/