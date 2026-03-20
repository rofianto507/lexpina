<?php
session_start();
if (!isset($_SESSION["id"]) || $_SESSION["id"] == "") {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
require_once("../config/configuration.php");
$type = $_GET['type'] ?? '';
$level = $_GET['level'] ?? '';
$id = intval($_GET['id'] ?? 0);
// --- Ambil filter kategori
$kategoriFilter = [];
if(!empty($_GET['kategori'])){
    if(is_array($_GET['kategori'])) $kategoriFilter = array_map('intval', $_GET['kategori']);
    else $kategoriFilter = array_map('intval', explode(',', $_GET['kategori']));
    $kategoriIn = implode(',', array_map('intval', $kategoriFilter));
}
$whereKategori = "";
if(!empty($kategoriIn)){
    if ($type === 'kriminalitas') {
        $whereKategori = "AND k.sub_kategori_id IN (
          SELECT id FROM kriminal_sub_kategoris WHERE kategori_id IN ($kategoriIn)
        )";
    } elseif ($type === 'kamtibmasmenonjol') {
        $whereKategori = "AND k.kategori_id IN ($kategoriIn)";
    } elseif ($type === 'lalin') {
        $whereKategori = "AND l.kategori_id IN ($kategoriIn)";
    } elseif ($type === 'lokasi') {
        $whereKategori = "AND l.kategori_id IN ($kategoriIn)";
    } elseif ($type === 'bencana') {
        $whereKategori = "AND l.kategori_id IN ($kategoriIn)";
    }
}

// --- Ambil filter tahun
$tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');
$whereTahun = "";
if($tahun) $whereTahun = "AND s.tahun = $tahun";
$res = [];

// Contoh kriminalitas, level kabupaten
if ($type == 'kriminalitas') {
  if ($level == 'provinsi') {
        $stmt = $pdo->prepare("
          SELECT
            k.*,
            po.nama AS polres_nama,
            kab.nama AS kabupaten_nama,
            s.nama AS sumber_nama,
            ka.nama as sub_kategori_nama,
            kat.nama as kategori_nama,
            s.tahun as sumber_tahun
          FROM kriminals k
            LEFT JOIN desas d ON k.desa_id = d.id
            LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
            LEFT JOIN kabupatens kab ON kc.kabupaten_id = kab.id
            LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
            LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
            LEFT JOIN polress po ON k.polres_id = po.id
            LEFT JOIN sumbers s ON k.sumber_id = s.id
          WHERE k.status=1 $whereKategori $whereTahun
        ");
        $stmt->execute();
    }else if ($level == 'polres') {
        $stmt = $pdo->prepare("
          SELECT
            k.*,
            po.nama AS polres_nama,
            kab.nama AS kabupaten_nama,
            s.nama AS sumber_nama,
            ka.nama as sub_kategori_nama,
            kat.nama as kategori_nama,
            s.tahun as sumber_tahun
          FROM kriminals k
            LEFT JOIN desas d ON k.desa_id = d.id
            LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
            LEFT JOIN kabupatens kab ON kc.kabupaten_id = kab.id
            LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
            LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
            LEFT JOIN polress po ON k.polres_id = po.id
            LEFT JOIN sumbers s ON k.sumber_id = s.id
          WHERE k.status=1 AND k.polres_id=? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }else if ($level == 'polsek') {
        $stmt = $pdo->prepare("
          SELECT
            k.*,
            po.nama AS polres_nama,
            kab.nama AS kabupaten_nama,
            s.nama AS sumber_nama,
            ka.nama as sub_kategori_nama,
            kat.nama as kategori_nama,
            s.tahun as sumber_tahun
          FROM kriminals k
            LEFT JOIN desas d ON k.desa_id = d.id
            LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
            LEFT JOIN kabupatens kab ON kc.kabupaten_id = kab.id
            LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
            LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                 
            LEFT JOIN polress po ON k.polres_id = po.id
            LEFT JOIN sumbers s ON k.sumber_id = s.id
          WHERE k.status=1 AND k.polsek_id=? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }else if ($level == 'desa') {
        $stmt = $pdo->prepare("
          SELECT
            k.*,
            po.nama AS polres_nama,
            kab.nama AS kabupaten_nama,
            s.nama AS sumber_nama,
            ka.nama as sub_kategori_nama,
            kat.nama as kategori_nama,
            s.tahun as sumber_tahun
          FROM kriminals k
            LEFT JOIN desas d ON k.desa_id = d.id
            LEFT JOIN kecamatans kc ON d.kecamatan_id = kc.id
            LEFT JOIN kabupatens kab ON kc.kabupaten_id = kab.id
            LEFT JOIN kriminal_sub_kategoris ka ON k.sub_kategori_id = ka.id
            LEFT JOIN kriminal_kategoris kat ON ka.kategori_id = kat.id                
            LEFT JOIN polress po ON k.polres_id = po.id
            LEFT JOIN sumbers s ON k.sumber_id = s.id
          WHERE k.status=1 AND d.id=? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    } 

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $res[] = $row;
    }
}

else if ($type == 'kamtibmasmenonjol') {
  if ($level == 'provinsi') {
       $stmt = $pdo->prepare("
          SELECT
              k.*,
              ka.nama AS kategori_nama,
              mo.nama AS modus_nama,
              jt.nama AS jenis_tkp_nama,
           
              d.nama AS desa_nama,
              po.nama AS polres_nama,
              ps.nama AS polsek_nama,
              s.nama AS sumber_dokumen_nama,
              s.tahun as sumber_tahun 
          FROM kamtibmass k
              LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
              LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
              LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
              LEFT JOIN desas d ON k.desa_id = d.id
              LEFT JOIN polress po ON k.polres_id = po.id
              LEFT JOIN polseks ps ON k.polsek_id = ps.id
              LEFT JOIN sumbers s ON k.sumber_id = s.id
          WHERE k.status = 1 AND k.is_menonjol = 1 $whereKategori $whereTahun
      ");
      $stmt->execute();
    }
   elseif ($level == 'kabupaten') {
        $stmt = $pdo->prepare("
        SELECT
              k.*,
              ka.nama AS kategori_nama,
              mo.nama AS modus_nama,
              jt.nama AS jenis_tkp_nama,
              d.nama AS desa_nama,
              po.nama AS polres_nama,
              ps.nama AS polsek_nama,
              s.nama AS sumber_dokumen_nama,
              s.tahun as sumber_tahun 
          FROM kamtibmass k
              LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
              LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
              LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
              LEFT JOIN desas d ON k.desa_id = d.id
              LEFT JOIN polress po ON k.polres_id = po.id
              LEFT JOIN polseks ps ON k.polsek_id = ps.id
              LEFT JOIN sumbers s ON k.sumber_id = s.id
        WHERE k.status = 1 AND k.is_menonjol = 1 
        AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = ?) 
         $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'kecamatan') {
        $stmt = $pdo->prepare("
        SELECT
              k.*,
              ka.nama AS kategori_nama,
              mo.nama AS modus_nama,
              jt.nama AS jenis_tkp_nama,
              d.nama AS desa_nama,
              po.nama AS polres_nama,
              ps.nama AS polsek_nama,
              s.nama AS sumber_dokumen_nama,
              s.tahun as sumber_tahun 
          FROM kamtibmass k
              LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
              LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
              LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
              LEFT JOIN desas d ON k.desa_id = d.id
              LEFT JOIN polress po ON k.polres_id = po.id
              LEFT JOIN polseks ps ON k.polsek_id = ps.id
              LEFT JOIN sumbers s ON k.sumber_id = s.id
        WHERE k.status = 1 AND k.is_menonjol = 1 AND d.kecamatan_id = ? 
         $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'desa') {
        $stmt = $pdo->prepare("
       SELECT
              k.*,
              ka.nama AS kategori_nama,
              mo.nama AS modus_nama,
              jt.nama AS jenis_tkp_nama,
              d.nama AS desa_nama,
              po.nama AS polres_nama,
              ps.nama AS polsek_nama,
              s.nama AS sumber_dokumen_nama,
              s.tahun as sumber_tahun 
          FROM kamtibmass k
              LEFT JOIN kamtibmas_kategoris ka ON k.kategori_id = ka.id
              LEFT JOIN modus_operandis mo ON k.modus_operandi_id = mo.id
              LEFT JOIN jenis_tkps jt ON k.jenis_tkp_id = jt.id
              LEFT JOIN desas d ON k.desa_id = d.id
              LEFT JOIN polress po ON k.polres_id = po.id
              LEFT JOIN polseks ps ON k.polsek_id = ps.id
              LEFT JOIN sumbers s ON k.sumber_id = s.id
        WHERE k.status = 1 AND k.is_menonjol = 1 AND d.id = ? 
         $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $res[] = $row;
    }
}
else if ($type == 'lalin') {
  if ($level == 'provinsi') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id, k.kabupaten_id
        ,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM lalins l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 $whereKategori $whereTahun  
        ");
        $stmt->execute();
    }
  elseif ($level == 'kabupaten') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id,
        k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM lalins l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = ?) 
          $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'kecamatan') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, 
        d.kecamatan_id, k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM lalins l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.kecamatan_id = ? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'desa') {
        $stmt = $pdo->prepare("
       SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id, 
       k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM lalins l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN lalin_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.id = ? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $res[] = $row;
    }
}
else if ($type == 'bencana') {
  if ($level == 'provinsi') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id, k.kabupaten_id
        ,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 $whereKategori $whereTahun  
        ");
        $stmt->execute();
    }
  elseif ($level == 'kabupaten') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id,
        k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.kecamatan_id IN (SELECT id FROM kecamatans WHERE kabupaten_id = ?) 
          $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'kecamatan') {
        $stmt = $pdo->prepare("
        SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, 
        d.kecamatan_id, k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.kecamatan_id = ? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }
    elseif ($level == 'desa') {
        $stmt = $pdo->prepare("
       SELECT l.*, c.nama as kategori_nama, d.nama as desa_nama, k.nama as kecamatan_nama, b.nama as kabupaten_nama, d.id as desa_id, d.kecamatan_id, 
       k.kabupaten_id,s.nama as sumber_dokumen_nama,s.tahun as sumber_tahun FROM bencanas l 
                    LEFT JOIN desas d ON l.desa_id=d.id
                    LEFT JOIN kecamatans k ON d.kecamatan_id = k.id
                    LEFT JOIN kabupatens b ON k.kabupaten_id = b.id
                    LEFT JOIN bencana_kategoris c ON l.kategori_id = c.id
                    LEFT JOIN sumbers s ON l.sumber_id = s.id
        WHERE l.status = 1 AND d.id = ? $whereKategori $whereTahun
        ");
        $stmt->execute([$id]);
    }

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $res[] = $row;
    }
}
else if ($type == 'lokasi') {
   if ($level == 'kabupaten') {
        $stmt = $pdo->prepare("
        SELECT l.*, k.nama as kategori_nama, s.nama as sumber_dokumen_nama FROM lokasis l 
                    LEFT JOIN lokasi_kategoris k ON l.kategori_id=k.id
                    LEFT JOIN sumbers s ON l.sumber_id=s.id WHERE l.status=1
        ");
        $stmt->execute();
    }
    

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $res[] = $row;
    }
}

echo json_encode($res);