<?php
/**
 * Copyright (c) 2014 Aleph Tav
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated 
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation 
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, 
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO 
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @link http://www.4leph.com
 * @copyright Copyright &copy; 2014 Aleph Tav
 * @license http://www.opensource.org/licenses/MIT
 */

namespace Aleph\Utils;

/**
 * Helps to validate a file, uploaded through a form and to move it to the specified destination.
 *
 * @author Aleph Tav <4lephtav@gmail.com>
 * @version 1.0.0
 * @package aleph.utils
 */
class UploadedFile
{
  /**
   * The information about the uploaded file.
   *
   * @var array $data
   * @access protected
   */
  protected $data = [];
  
  /**
   * The code of the last happened error.
   *
   * @var integer $error
   * @access protected
   */
  protected $error = 0;
  
  /**
   * The list of default extensions corresponding to file mime types.
   *
   * @var array $defaultExtensions
   * @access protected
   */
  protected $defaultExtensions = [
        'application/andrew-inset' => 'ez',
        'application/applixware' => 'aw',
        'application/atom+xml' => 'atom',
        'application/atomcat+xml' => 'atomcat',
        'application/atomsvc+xml' => 'atomsvc',
        'application/ccxml+xml' => 'ccxml',
        'application/cdmi-capability' => 'cdmia',
        'application/cdmi-container' => 'cdmic',
        'application/cdmi-domain' => 'cdmid',
        'application/cdmi-object' => 'cdmio',
        'application/cdmi-queue' => 'cdmiq',
        'application/cu-seeme' => 'cu',
        'application/davmount+xml' => 'davmount',
        'application/dssc+der' => 'dssc',
        'application/dssc+xml' => 'xdssc',
        'application/ecmascript' => 'ecma',
        'application/emma+xml' => 'emma',
        'application/epub+zip' => 'epub',
        'application/exi' => 'exi',
        'application/font-tdpfr' => 'pfr',
        'application/hyperstudio' => 'stk',
        'application/inkml+xml' => 'ink',
        'application/ipfix' => 'ipfix',
        'application/java-archive' => 'jar',
        'application/java-serialized-object' => 'ser',
        'application/java-vm' => 'class',
        'application/javascript' => 'js',
        'application/json' => 'json',
        'application/lost+xml' => 'lostxml',
        'application/mac-binhex40' => 'hqx',
        'application/mac-compactpro' => 'cpt',
        'application/mads+xml' => 'mads',
        'application/marc' => 'mrc',
        'application/marcxml+xml' => 'mrcx',
        'application/mathematica' => 'ma',
        'application/mathml+xml' => 'mathml',
        'application/mbox' => 'mbox',
        'application/mediaservercontrol+xml' => 'mscml',
        'application/metalink4+xml' => 'meta4',
        'application/mets+xml' => 'mets',
        'application/mods+xml' => 'mods',
        'application/mp21' => 'm21',
        'application/mp4' => 'mp4s',
        'application/msword' => 'doc',
        'application/mxf' => 'mxf',
        'application/octet-stream' => 'bin',
        'application/oda' => 'oda',
        'application/oebps-package+xml' => 'opf',
        'application/ogg' => 'ogx',
        'application/onenote' => 'onetoc',
        'application/oxps' => 'oxps',
        'application/patch-ops-error+xml' => 'xer',
        'application/pdf' => 'pdf',
        'application/pgp-encrypted' => 'pgp',
        'application/pgp-signature' => 'asc',
        'application/pics-rules' => 'prf',
        'application/pkcs10' => 'p10',
        'application/pkcs7-mime' => 'p7m',
        'application/pkcs7-signature' => 'p7s',
        'application/pkcs8' => 'p8',
        'application/pkix-attr-cert' => 'ac',
        'application/pkix-cert' => 'cer',
        'application/pkix-crl' => 'crl',
        'application/pkix-pkipath' => 'pkipath',
        'application/pkixcmp' => 'pki',
        'application/pls+xml' => 'pls',
        'application/postscript' => 'ai',
        'application/prs.cww' => 'cww',
        'application/pskc+xml' => 'pskcxml',
        'application/rdf+xml' => 'rdf',
        'application/reginfo+xml' => 'rif',
        'application/relax-ng-compact-syntax' => 'rnc',
        'application/resource-lists+xml' => 'rl',
        'application/resource-lists-diff+xml' => 'rld',
        'application/rls-services+xml' => 'rs',
        'application/rpki-ghostbusters' => 'gbr',
        'application/rpki-manifest' => 'mft',
        'application/rpki-roa' => 'roa',
        'application/rsd+xml' => 'rsd',
        'application/rss+xml' => 'rss',
        'application/rtf' => 'rtf',
        'application/sbml+xml' => 'sbml',
        'application/scvp-cv-request' => 'scq',
        'application/scvp-cv-response' => 'scs',
        'application/scvp-vp-request' => 'spq',
        'application/scvp-vp-response' => 'spp',
        'application/sdp' => 'sdp',
        'application/set-payment-initiation' => 'setpay',
        'application/set-registration-initiation' => 'setreg',
        'application/shf+xml' => 'shf',
        'application/smil+xml' => 'smi',
        'application/sparql-query' => 'rq',
        'application/sparql-results+xml' => 'srx',
        'application/srgs' => 'gram',
        'application/srgs+xml' => 'grxml',
        'application/sru+xml' => 'sru',
        'application/ssml+xml' => 'ssml',
        'application/tei+xml' => 'tei',
        'application/thraud+xml' => 'tfi',
        'application/timestamped-data' => 'tsd',
        'application/vnd.3gpp.pic-bw-large' => 'plb',
        'application/vnd.3gpp.pic-bw-small' => 'psb',
        'application/vnd.3gpp.pic-bw-var' => 'pvb',
        'application/vnd.3gpp2.tcap' => 'tcap',
        'application/vnd.3m.post-it-notes' => 'pwn',
        'application/vnd.accpac.simply.aso' => 'aso',
        'application/vnd.accpac.simply.imp' => 'imp',
        'application/vnd.acucobol' => 'acu',
        'application/vnd.acucorp' => 'atc',
        'application/vnd.adobe.air-application-installer-package+zip' => 'air',
        'application/vnd.adobe.fxp' => 'fxp',
        'application/vnd.adobe.xdp+xml' => 'xdp',
        'application/vnd.adobe.xfdf' => 'xfdf',
        'application/vnd.ahead.space' => 'ahead',
        'application/vnd.airzip.filesecure.azf' => 'azf',
        'application/vnd.airzip.filesecure.azs' => 'azs',
        'application/vnd.amazon.ebook' => 'azw',
        'application/vnd.americandynamics.acc' => 'acc',
        'application/vnd.amiga.ami' => 'ami',
        'application/vnd.android.package-archive' => 'apk',
        'application/vnd.anser-web-certificate-issue-initiation' => 'cii',
        'application/vnd.anser-web-funds-transfer-initiation' => 'fti',
        'application/vnd.antix.game-component' => 'atx',
        'application/vnd.apple.installer+xml' => 'mpkg',
        'application/vnd.apple.mpegurl' => 'm3u8',
        'application/vnd.aristanetworks.swi' => 'swi',
        'application/vnd.astraea-software.iota' => 'iota',
        'application/vnd.audiograph' => 'aep',
        'application/vnd.blueice.multipass' => 'mpm',
        'application/vnd.bmi' => 'bmi',
        'application/vnd.businessobjects' => 'rep',
        'application/vnd.chemdraw+xml' => 'cdxml',
        'application/vnd.chipnuts.karaoke-mmd' => 'mmd',
        'application/vnd.cinderella' => 'cdy',
        'application/vnd.claymore' => 'cla',
        'application/vnd.cloanto.rp9' => 'rp9',
        'application/vnd.clonk.c4group' => 'c4g',
        'application/vnd.cluetrust.cartomobile-config' => 'c11amc',
        'application/vnd.cluetrust.cartomobile-config-pkg' => 'c11amz',
        'application/vnd.commonspace' => 'csp',
        'application/vnd.contact.cmsg' => 'cdbcmsg',
        'application/vnd.cosmocaller' => 'cmc',
        'application/vnd.crick.clicker' => 'clkx',
        'application/vnd.crick.clicker.keyboard' => 'clkk',
        'application/vnd.crick.clicker.palette' => 'clkp',
        'application/vnd.crick.clicker.template' => 'clkt',
        'application/vnd.crick.clicker.wordbank' => 'clkw',
        'application/vnd.criticaltools.wbs+xml' => 'wbs',
        'application/vnd.ctc-posml' => 'pml',
        'application/vnd.cups-ppd' => 'ppd',
        'application/vnd.curl.car' => 'car',
        'application/vnd.curl.pcurl' => 'pcurl',
        'application/vnd.data-vision.rdz' => 'rdz',
        'application/vnd.dece.data' => 'uvf',
        'application/vnd.dece.ttml+xml' => 'uvt',
        'application/vnd.dece.unspecified' => 'uvx',
        'application/vnd.dece.zip' => 'uvz',
        'application/vnd.denovo.fcselayout-link' => 'fe_launch',
        'application/vnd.dna' => 'dna',
        'application/vnd.dolby.mlp' => 'mlp',
        'application/vnd.dpgraph' => 'dpg',
        'application/vnd.dreamfactory' => 'dfac',
        'application/vnd.dvb.ait' => 'ait',
        'application/vnd.dvb.service' => 'svc',
        'application/vnd.dynageo' => 'geo',
        'application/vnd.ecowin.chart' => 'mag',
        'application/vnd.enliven' => 'nml',
        'application/vnd.epson.esf' => 'esf',
        'application/vnd.epson.msf' => 'msf',
        'application/vnd.epson.quickanime' => 'qam',
        'application/vnd.epson.salt' => 'slt',
        'application/vnd.epson.ssf' => 'ssf',
        'application/vnd.eszigno3+xml' => 'es3',
        'application/vnd.ezpix-album' => 'ez2',
        'application/vnd.ezpix-package' => 'ez3',
        'application/vnd.fdf' => 'fdf',
        'application/vnd.fdsn.mseed' => 'mseed',
        'application/vnd.fdsn.seed' => 'seed',
        'application/vnd.flographit' => 'gph',
        'application/vnd.fluxtime.clip' => 'ftc',
        'application/vnd.framemaker' => 'fm',
        'application/vnd.frogans.fnc' => 'fnc',
        'application/vnd.frogans.ltf' => 'ltf',
        'application/vnd.fsc.weblaunch' => 'fsc',
        'application/vnd.fujitsu.oasys' => 'oas',
        'application/vnd.fujitsu.oasys2' => 'oa2',
        'application/vnd.fujitsu.oasys3' => 'oa3',
        'application/vnd.fujitsu.oasysgp' => 'fg5',
        'application/vnd.fujitsu.oasysprs' => 'bh2',
        'application/vnd.fujixerox.ddd' => 'ddd',
        'application/vnd.fujixerox.docuworks' => 'xdw',
        'application/vnd.fujixerox.docuworks.binder' => 'xbd',
        'application/vnd.fuzzysheet' => 'fzs',
        'application/vnd.genomatix.tuxedo' => 'txd',
        'application/vnd.geogebra.file' => 'ggb',
        'application/vnd.geogebra.tool' => 'ggt',
        'application/vnd.geometry-explorer' => 'gex',
        'application/vnd.geonext' => 'gxt',
        'application/vnd.geoplan' => 'g2w',
        'application/vnd.geospace' => 'g3w',
        'application/vnd.gmx' => 'gmx',
        'application/vnd.google-earth.kml+xml' => 'kml',
        'application/vnd.google-earth.kmz' => 'kmz',
        'application/vnd.grafeq' => 'gqf',
        'application/vnd.groove-account' => 'gac',
        'application/vnd.groove-help' => 'ghf',
        'application/vnd.groove-identity-message' => 'gim',
        'application/vnd.groove-injector' => 'grv',
        'application/vnd.groove-tool-message' => 'gtm',
        'application/vnd.groove-tool-template' => 'tpl',
        'application/vnd.groove-vcard' => 'vcg',
        'application/vnd.hal+xml' => 'hal',
        'application/vnd.handheld-entertainment+xml' => 'zmm',
        'application/vnd.hbci' => 'hbci',
        'application/vnd.hhe.lesson-player' => 'les',
        'application/vnd.hp-hpgl' => 'hpgl',
        'application/vnd.hp-hpid' => 'hpid',
        'application/vnd.hp-hps' => 'hps',
        'application/vnd.hp-jlyt' => 'jlt',
        'application/vnd.hp-pcl' => 'pcl',
        'application/vnd.hp-pclxl' => 'pclxl',
        'application/vnd.hydrostatix.sof-data' => 'sfd-hdstx',
        'application/vnd.hzn-3d-crossword' => 'x3d',
        'application/vnd.ibm.minipay' => 'mpy',
        'application/vnd.ibm.modcap' => 'afp',
        'application/vnd.ibm.rights-management' => 'irm',
        'application/vnd.ibm.secure-container' => 'sc',
        'application/vnd.iccprofile' => 'icc',
        'application/vnd.igloader' => 'igl',
        'application/vnd.immervision-ivp' => 'ivp',
        'application/vnd.immervision-ivu' => 'ivu',
        'application/vnd.insors.igm' => 'igm',
        'application/vnd.intercon.formnet' => 'xpw',
        'application/vnd.intergeo' => 'i2g',
        'application/vnd.intu.qbo' => 'qbo',
        'application/vnd.intu.qfx' => 'qfx',
        'application/vnd.ipunplugged.rcprofile' => 'rcprofile',
        'application/vnd.irepository.package+xml' => 'irp',
        'application/vnd.is-xpr' => 'xpr',
        'application/vnd.isac.fcs' => 'fcs',
        'application/vnd.jam' => 'jam',
        'application/vnd.jcp.javame.midlet-rms' => 'rms',
        'application/vnd.jisp' => 'jisp',
        'application/vnd.joost.joda-archive' => 'joda',
        'application/vnd.kahootz' => 'ktz',
        'application/vnd.kde.karbon' => 'karbon',
        'application/vnd.kde.kchart' => 'chrt',
        'application/vnd.kde.kformula' => 'kfo',
        'application/vnd.kde.kivio' => 'flw',
        'application/vnd.kde.kontour' => 'kon',
        'application/vnd.kde.kpresenter' => 'kpr',
        'application/vnd.kde.kspread' => 'ksp',
        'application/vnd.kde.kword' => 'kwd',
        'application/vnd.kenameaapp' => 'htke',
        'application/vnd.kidspiration' => 'kia',
        'application/vnd.kinar' => 'kne',
        'application/vnd.koan' => 'skp',
        'application/vnd.kodak-descriptor' => 'sse',
        'application/vnd.las.las+xml' => 'lasxml',
        'application/vnd.llamagraphics.life-balance.desktop' => 'lbd',
        'application/vnd.llamagraphics.life-balance.exchange+xml' => 'lbe',
        'application/vnd.lotus-1-2-3' => '123',
        'application/vnd.lotus-approach' => 'apr',
        'application/vnd.lotus-freelance' => 'pre',
        'application/vnd.lotus-notes' => 'nsf',
        'application/vnd.lotus-organizer' => 'org',
        'application/vnd.lotus-screencam' => 'scm',
        'application/vnd.lotus-wordpro' => 'lwp',
        'application/vnd.macports.portpkg' => 'portpkg',
        'application/vnd.mcd' => 'mcd',
        'application/vnd.medcalcdata' => 'mc1',
        'application/vnd.mediastation.cdkey' => 'cdkey',
        'application/vnd.mfer' => 'mwf',
        'application/vnd.mfmp' => 'mfm',
        'application/vnd.micrografx.flo' => 'flo',
        'application/vnd.micrografx.igx' => 'igx',
        'application/vnd.mif' => 'mif',
        'application/vnd.mobius.daf' => 'daf',
        'application/vnd.mobius.dis' => 'dis',
        'application/vnd.mobius.mbk' => 'mbk',
        'application/vnd.mobius.mqy' => 'mqy',
        'application/vnd.mobius.msl' => 'msl',
        'application/vnd.mobius.plc' => 'plc',
        'application/vnd.mobius.txf' => 'txf',
        'application/vnd.mophun.application' => 'mpn',
        'application/vnd.mophun.certificate' => 'mpc',
        'application/vnd.mozilla.xul+xml' => 'xul',
        'application/vnd.ms-artgalry' => 'cil',
        'application/vnd.ms-cab-compressed' => 'cab',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.ms-excel.addin.macroenabled.12' => 'xlam',
        'application/vnd.ms-excel.sheet.binary.macroenabled.12' => 'xlsb',
        'application/vnd.ms-excel.sheet.macroenabled.12' => 'xlsm',
        'application/vnd.ms-excel.template.macroenabled.12' => 'xltm',
        'application/vnd.ms-fontobject' => 'eot',
        'application/vnd.ms-htmlhelp' => 'chm',
        'application/vnd.ms-ims' => 'ims',
        'application/vnd.ms-lrm' => 'lrm',
        'application/vnd.ms-officetheme' => 'thmx',
        'application/vnd.ms-pki.seccat' => 'cat',
        'application/vnd.ms-pki.stl' => 'stl',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.ms-powerpoint.addin.macroenabled.12' => 'ppam',
        'application/vnd.ms-powerpoint.presentation.macroenabled.12' => 'pptm',
        'application/vnd.ms-powerpoint.slide.macroenabled.12' => 'sldm',
        'application/vnd.ms-powerpoint.slideshow.macroenabled.12' => 'ppsm',
        'application/vnd.ms-powerpoint.template.macroenabled.12' => 'potm',
        'application/vnd.ms-project' => 'mpp',
        'application/vnd.ms-word.document.macroenabled.12' => 'docm',
        'application/vnd.ms-word.template.macroenabled.12' => 'dotm',
        'application/vnd.ms-works' => 'wps',
        'application/vnd.ms-wpl' => 'wpl',
        'application/vnd.ms-xpsdocument' => 'xps',
        'application/vnd.mseq' => 'mseq',
        'application/vnd.musician' => 'mus',
        'application/vnd.muvee.style' => 'msty',
        'application/vnd.mynfc' => 'taglet',
        'application/vnd.neurolanguage.nlu' => 'nlu',
        'application/vnd.noblenet-directory' => 'nnd',
        'application/vnd.noblenet-sealer' => 'nns',
        'application/vnd.noblenet-web' => 'nnw',
        'application/vnd.nokia.n-gage.data' => 'ngdat',
        'application/vnd.nokia.n-gage.symbian.install' => 'n-gage',
        'application/vnd.nokia.radio-preset' => 'rpst',
        'application/vnd.nokia.radio-presets' => 'rpss',
        'application/vnd.novadigm.edm' => 'edm',
        'application/vnd.novadigm.edx' => 'edx',
        'application/vnd.novadigm.ext' => 'ext',
        'application/vnd.oasis.opendocument.chart' => 'odc',
        'application/vnd.oasis.opendocument.chart-template' => 'otc',
        'application/vnd.oasis.opendocument.database' => 'odb',
        'application/vnd.oasis.opendocument.formula' => 'odf',
        'application/vnd.oasis.opendocument.formula-template' => 'odft',
        'application/vnd.oasis.opendocument.graphics' => 'odg',
        'application/vnd.oasis.opendocument.graphics-template' => 'otg',
        'application/vnd.oasis.opendocument.image' => 'odi',
        'application/vnd.oasis.opendocument.image-template' => 'oti',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
        'application/vnd.oasis.opendocument.presentation-template' => 'otp',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.oasis.opendocument.spreadsheet-template' => 'ots',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.text-master' => 'odm',
        'application/vnd.oasis.opendocument.text-template' => 'ott',
        'application/vnd.oasis.opendocument.text-web' => 'oth',
        'application/vnd.olpc-sugar' => 'xo',
        'application/vnd.oma.dd2+xml' => 'dd2',
        'application/vnd.openofficeorg.extension' => 'oxt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.openxmlformats-officedocument.presentationml.slide' => 'sldx',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow' => 'ppsx',
        'application/vnd.openxmlformats-officedocument.presentationml.template' => 'potx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template' => 'xltx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template' => 'dotx',
        'application/vnd.osgeo.mapguide.package' => 'mgp',
        'application/vnd.osgi.dp' => 'dp',
        'application/vnd.palm' => 'pdb',
        'application/vnd.pawaafile' => 'paw',
        'application/vnd.pg.format' => 'str',
        'application/vnd.pg.osasli' => 'ei6',
        'application/vnd.picsel' => 'efif',
        'application/vnd.pmi.widget' => 'wg',
        'application/vnd.pocketlearn' => 'plf',
        'application/vnd.powerbuilder6' => 'pbd',
        'application/vnd.previewsystems.box' => 'box',
        'application/vnd.proteus.magazine' => 'mgz',
        'application/vnd.publishare-delta-tree' => 'qps',
        'application/vnd.pvi.ptid1' => 'ptid',
        'application/vnd.quark.quarkxpress' => 'qxd',
        'application/vnd.realvnc.bed' => 'bed',
        'application/vnd.recordare.musicxml' => 'mxl',
        'application/vnd.recordare.musicxml+xml' => 'musicxml',
        'application/vnd.rig.cryptonote' => 'cryptonote',
        'application/vnd.rim.cod' => 'cod',
        'application/vnd.rn-realmedia' => 'rm',
        'application/vnd.route66.link66+xml' => 'link66',
        'application/vnd.sailingtracker.track' => 'st',
        'application/vnd.seemail' => 'see',
        'application/vnd.sema' => 'sema',
        'application/vnd.semd' => 'semd',
        'application/vnd.semf' => 'semf',
        'application/vnd.shana.informed.formdata' => 'ifm',
        'application/vnd.shana.informed.formtemplate' => 'itp',
        'application/vnd.shana.informed.interchange' => 'iif',
        'application/vnd.shana.informed.package' => 'ipk',
        'application/vnd.simtech-mindmapper' => 'twd',
        'application/vnd.smaf' => 'mmf',
        'application/vnd.smart.teacher' => 'teacher',
        'application/vnd.solent.sdkm+xml' => 'sdkm',
        'application/vnd.spotfire.dxp' => 'dxp',
        'application/vnd.spotfire.sfs' => 'sfs',
        'application/vnd.stardivision.calc' => 'sdc',
        'application/vnd.stardivision.draw' => 'sda',
        'application/vnd.stardivision.impress' => 'sdd',
        'application/vnd.stardivision.math' => 'smf',
        'application/vnd.stardivision.writer' => 'sdw',
        'application/vnd.stardivision.writer-global' => 'sgl',
        'application/vnd.stepmania.package' => 'smzip',
        'application/vnd.stepmania.stepchart' => 'sm',
        'application/vnd.sun.xml.calc' => 'sxc',
        'application/vnd.sun.xml.calc.template' => 'stc',
        'application/vnd.sun.xml.draw' => 'sxd',
        'application/vnd.sun.xml.draw.template' => 'std',
        'application/vnd.sun.xml.impress' => 'sxi',
        'application/vnd.sun.xml.impress.template' => 'sti',
        'application/vnd.sun.xml.math' => 'sxm',
        'application/vnd.sun.xml.writer' => 'sxw',
        'application/vnd.sun.xml.writer.global' => 'sxg',
        'application/vnd.sun.xml.writer.template' => 'stw',
        'application/vnd.sus-calendar' => 'sus',
        'application/vnd.svd' => 'svd',
        'application/vnd.symbian.install' => 'sis',
        'application/vnd.syncml+xml' => 'xsm',
        'application/vnd.syncml.dm+wbxml' => 'bdm',
        'application/vnd.syncml.dm+xml' => 'xdm',
        'application/vnd.tao.intent-module-archive' => 'tao',
        'application/vnd.tcpdump.pcap' => 'pcap',
        'application/vnd.tmobile-livetv' => 'tmo',
        'application/vnd.trid.tpt' => 'tpt',
        'application/vnd.triscape.mxs' => 'mxs',
        'application/vnd.trueapp' => 'tra',
        'application/vnd.ufdl' => 'ufd',
        'application/vnd.uiq.theme' => 'utz',
        'application/vnd.umajin' => 'umj',
        'application/vnd.unity' => 'unityweb',
        'application/vnd.uoml+xml' => 'uoml',
        'application/vnd.vcx' => 'vcx',
        'application/vnd.visio' => 'vsd',
        'application/vnd.visionary' => 'vis',
        'application/vnd.vsf' => 'vsf',
        'application/vnd.wap.wbxml' => 'wbxml',
        'application/vnd.wap.wmlc' => 'wmlc',
        'application/vnd.wap.wmlscriptc' => 'wmlsc',
        'application/vnd.webturbo' => 'wtb',
        'application/vnd.wolfram.player' => 'nbp',
        'application/vnd.wordperfect' => 'wpd',
        'application/vnd.wqd' => 'wqd',
        'application/vnd.wt.stf' => 'stf',
        'application/vnd.xara' => 'xar',
        'application/vnd.xfdl' => 'xfdl',
        'application/vnd.yamaha.hv-dic' => 'hvd',
        'application/vnd.yamaha.hv-script' => 'hvs',
        'application/vnd.yamaha.hv-voice' => 'hvp',
        'application/vnd.yamaha.openscoreformat' => 'osf',
        'application/vnd.yamaha.openscoreformat.osfpvg+xml' => 'osfpvg',
        'application/vnd.yamaha.smaf-audio' => 'saf',
        'application/vnd.yamaha.smaf-phrase' => 'spf',
        'application/vnd.yellowriver-custom-menu' => 'cmp',
        'application/vnd.zul' => 'zir',
        'application/vnd.zzazz.deck+xml' => 'zaz',
        'application/voicexml+xml' => 'vxml',
        'application/widget' => 'wgt',
        'application/winhlp' => 'hlp',
        'application/wsdl+xml' => 'wsdl',
        'application/wspolicy+xml' => 'wspolicy',
        'application/x-7z-compressed' => '7z',
        'application/x-abiword' => 'abw',
        'application/x-ace-compressed' => 'ace',
        'application/x-authorware-bin' => 'aab',
        'application/x-authorware-map' => 'aam',
        'application/x-authorware-seg' => 'aas',
        'application/x-bcpio' => 'bcpio',
        'application/x-bittorrent' => 'torrent',
        'application/x-bzip' => 'bz',
        'application/x-bzip2' => 'bz2',
        'application/x-cdlink' => 'vcd',
        'application/x-chat' => 'chat',
        'application/x-chess-pgn' => 'pgn',
        'application/x-cpio' => 'cpio',
        'application/x-csh' => 'csh',
        'application/x-debian-package' => 'deb',
        'application/x-director' => 'dir',
        'application/x-doom' => 'wad',
        'application/x-dtbncx+xml' => 'ncx',
        'application/x-dtbook+xml' => 'dtb',
        'application/x-dtbresource+xml' => 'res',
        'application/x-dvi' => 'dvi',
        'application/x-font-bdf' => 'bdf',
        'application/x-font-ghostscript' => 'gsf',
        'application/x-font-linux-psf' => 'psf',
        'application/x-font-otf' => 'otf',
        'application/x-font-pcf' => 'pcf',
        'application/x-font-snf' => 'snf',
        'application/x-font-ttf' => 'ttf',
        'application/x-font-type1' => 'pfa',
        'application/x-font-woff' => 'woff',
        'application/x-futuresplash' => 'spl',
        'application/x-gnumeric' => 'gnumeric',
        'application/x-gtar' => 'gtar',
        'application/x-hdf' => 'hdf',
        'application/x-java-jnlp-file' => 'jnlp',
        'application/x-latex' => 'latex',
        'application/x-mobipocket-ebook' => 'prc',
        'application/x-ms-application' => 'application',
        'application/x-ms-wmd' => 'wmd',
        'application/x-ms-wmz' => 'wmz',
        'application/x-ms-xbap' => 'xbap',
        'application/x-msaccess' => 'mdb',
        'application/x-msbinder' => 'obd',
        'application/x-mscardfile' => 'crd',
        'application/x-msclip' => 'clp',
        'application/x-msdownload' => 'exe',
        'application/x-msmediaview' => 'mvb',
        'application/x-msmetafile' => 'wmf',
        'application/x-msmoney' => 'mny',
        'application/x-mspublisher' => 'pub',
        'application/x-msschedule' => 'scd',
        'application/x-msterminal' => 'trm',
        'application/x-mswrite' => 'wri',
        'application/x-netcdf' => 'nc',
        'application/x-pkcs12' => 'p12',
        'application/x-pkcs7-certificates' => 'p7b',
        'application/x-pkcs7-certreqresp' => 'p7r',
        'application/x-rar-compressed' => 'rar',
        'application/x-rar' => 'rar',
        'application/x-sh' => 'sh',
        'application/x-shar' => 'shar',
        'application/x-shockwave-flash' => 'swf',
        'application/x-silverlight-app' => 'xap',
        'application/x-stuffit' => 'sit',
        'application/x-stuffitx' => 'sitx',
        'application/x-sv4cpio' => 'sv4cpio',
        'application/x-sv4crc' => 'sv4crc',
        'application/x-tar' => 'tar',
        'application/x-tcl' => 'tcl',
        'application/x-tex' => 'tex',
        'application/x-tex-tfm' => 'tfm',
        'application/x-texinfo' => 'texinfo',
        'application/x-ustar' => 'ustar',
        'application/x-wais-source' => 'src',
        'application/x-x509-ca-cert' => 'der',
        'application/x-xfig' => 'fig',
        'application/x-xpinstall' => 'xpi',
        'application/xcap-diff+xml' => 'xdf',
        'application/xenc+xml' => 'xenc',
        'application/xhtml+xml' => 'xhtml',
        'application/xml' => 'xml',
        'application/xml-dtd' => 'dtd',
        'application/xop+xml' => 'xop',
        'application/xslt+xml' => 'xslt',
        'application/xspf+xml' => 'xspf',
        'application/xv+xml' => 'mxml',
        'application/yang' => 'yang',
        'application/yin+xml' => 'yin',
        'application/zip' => 'zip',
        'audio/adpcm' => 'adp',
        'audio/basic' => 'au',
        'audio/midi' => 'mid',
        'audio/mp4' => 'mp4a',
        'audio/mpeg' => 'mpga',
        'audio/ogg' => 'oga',
        'audio/vnd.dece.audio' => 'uva',
        'audio/vnd.digital-winds' => 'eol',
        'audio/vnd.dra' => 'dra',
        'audio/vnd.dts' => 'dts',
        'audio/vnd.dts.hd' => 'dtshd',
        'audio/vnd.lucent.voice' => 'lvp',
        'audio/vnd.ms-playready.media.pya' => 'pya',
        'audio/vnd.nuera.ecelp4800' => 'ecelp4800',
        'audio/vnd.nuera.ecelp7470' => 'ecelp7470',
        'audio/vnd.nuera.ecelp9600' => 'ecelp9600',
        'audio/vnd.rip' => 'rip',
        'audio/webm' => 'weba',
        'audio/x-aac' => 'aac',
        'audio/x-aiff' => 'aif',
        'audio/x-mpegurl' => 'm3u',
        'audio/x-ms-wax' => 'wax',
        'audio/x-ms-wma' => 'wma',
        'audio/x-pn-realaudio' => 'ram',
        'audio/x-pn-realaudio-plugin' => 'rmp',
        'audio/x-wav' => 'wav',
        'chemical/x-cdx' => 'cdx',
        'chemical/x-cif' => 'cif',
        'chemical/x-cmdf' => 'cmdf',
        'chemical/x-cml' => 'cml',
        'chemical/x-csml' => 'csml',
        'chemical/x-xyz' => 'xyz',
        'image/bmp' => 'bmp',
        'image/cgm' => 'cgm',
        'image/g3fax' => 'g3',
        'image/gif' => 'gif',
        'image/ief' => 'ief',
        'image/jpeg' => 'jpeg',
        'image/ktx' => 'ktx',
        'image/png' => 'png',
        'image/prs.btif' => 'btif',
        'image/svg+xml' => 'svg',
        'image/tiff' => 'tiff',
        'image/vnd.adobe.photoshop' => 'psd',
        'image/vnd.dece.graphic' => 'uvi',
        'image/vnd.dvb.subtitle' => 'sub',
        'image/vnd.djvu' => 'djvu',
        'image/vnd.dwg' => 'dwg',
        'image/vnd.dxf' => 'dxf',
        'image/vnd.fastbidsheet' => 'fbs',
        'image/vnd.fpx' => 'fpx',
        'image/vnd.fst' => 'fst',
        'image/vnd.fujixerox.edmics-mmr' => 'mmr',
        'image/vnd.fujixerox.edmics-rlc' => 'rlc',
        'image/vnd.ms-modi' => 'mdi',
        'image/vnd.net-fpx' => 'npx',
        'image/vnd.wap.wbmp' => 'wbmp',
        'image/vnd.xiff' => 'xif',
        'image/webp' => 'webp',
        'image/x-cmu-raster' => 'ras',
        'image/x-cmx' => 'cmx',
        'image/x-freehand' => 'fh',
        'image/x-icon' => 'ico',
        'image/x-pcx' => 'pcx',
        'image/x-pict' => 'pic',
        'image/x-portable-anymap' => 'pnm',
        'image/x-portable-bitmap' => 'pbm',
        'image/x-portable-graymap' => 'pgm',
        'image/x-portable-pixmap' => 'ppm',
        'image/x-rgb' => 'rgb',
        'image/x-xbitmap' => 'xbm',
        'image/x-xpixmap' => 'xpm',
        'image/x-xwindowdump' => 'xwd',
        'message/rfc822' => 'eml',
        'model/iges' => 'igs',
        'model/mesh' => 'msh',
        'model/vnd.collada+xml' => 'dae',
        'model/vnd.dwf' => 'dwf',
        'model/vnd.gdl' => 'gdl',
        'model/vnd.gtw' => 'gtw',
        'model/vnd.mts' => 'mts',
        'model/vnd.vtu' => 'vtu',
        'model/vrml' => 'wrl',
        'text/calendar' => 'ics',
        'text/css' => 'css',
        'text/csv' => 'csv',
        'text/html' => 'html',
        'text/n3' => 'n3',
        'text/plain' => 'txt',
        'text/prs.lines.tag' => 'dsc',
        'text/richtext' => 'rtx',
        'text/sgml' => 'sgml',
        'text/tab-separated-values' => 'tsv',
        'text/troff' => 't',
        'text/turtle' => 'ttl',
        'text/uri-list' => 'uri',
        'text/vcard' => 'vcard',
        'text/vnd.curl' => 'curl',
        'text/vnd.curl.dcurl' => 'dcurl',
        'text/vnd.curl.scurl' => 'scurl',
        'text/vnd.curl.mcurl' => 'mcurl',
        'text/vnd.dvb.subtitle' => 'sub',
        'text/vnd.fly' => 'fly',
        'text/vnd.fmi.flexstor' => 'flx',
        'text/vnd.graphviz' => 'gv',
        'text/vnd.in3d.3dml' => '3dml',
        'text/vnd.in3d.spot' => 'spot',
        'text/vnd.sun.j2me.app-descriptor' => 'jad',
        'text/vnd.wap.wml' => 'wml',
        'text/vnd.wap.wmlscript' => 'wmls',
        'text/x-asm' => 's',
        'text/x-c' => 'c',
        'text/x-fortran' => 'f',
        'text/x-pascal' => 'p',
        'text/x-java-source' => 'java',
        'text/x-setext' => 'etx',
        'text/x-uuencode' => 'uu',
        'text/x-vcalendar' => 'vcs',
        'text/x-vcard' => 'vcf',
        'video/3gpp' => '3gp',
        'video/3gpp2' => '3g2',
        'video/h261' => 'h261',
        'video/h263' => 'h263',
        'video/h264' => 'h264',
        'video/jpeg' => 'jpgv',
        'video/jpm' => 'jpm',
        'video/mj2' => 'mj2',
        'video/mp4' => 'mp4',
        'video/mpeg' => 'mpeg',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'qt',
        'video/vnd.dece.hd' => 'uvh',
        'video/vnd.dece.mobile' => 'uvm',
        'video/vnd.dece.pd' => 'uvp',
        'video/vnd.dece.sd' => 'uvs',
        'video/vnd.dece.video' => 'uvv',
        'video/vnd.dvb.file' => 'dvb',
        'video/vnd.fvt' => 'fvt',
        'video/vnd.mpegurl' => 'mxu',
        'video/vnd.ms-playready.media.pyv' => 'pyv',
        'video/vnd.uvvu.mp4' => 'uvu',
        'video/vnd.vivo' => 'viv',
        'video/webm' => 'webm',
        'video/x-f4v' => 'f4v',
        'video/x-fli' => 'fli',
        'video/x-flv' => 'flv',
        'video/x-m4v' => 'm4v',
        'video/x-ms-asf' => 'asf',
        'video/x-ms-wm' => 'wm',
        'video/x-ms-wmv' => 'wmv',
        'video/x-ms-wmx' => 'wmx',
        'video/x-ms-wvx' => 'wvx',
        'video/x-msvideo' => 'avi',
        'video/x-sgi-movie' => 'movie',
        'x-conference/x-cooltalk' => 'ice'];
  
  /**
   * The desired name of the uploaded file.
   * If this property is not defined the uploaded file will have the original name.
   *
   * @var string $name
   * @access public
   */
  public $name = null;
  
  /**
   * Determines whether the name of the uploaded file should be unique.
   *
   * @var boolean $unique
   * @access public
   */
  public $unique = false;
  
  /**
   * The desired destination directory for the uploaded file.
   *
   * @var string $destination
   * @access public
   */
  public $destination = null;
  
  /**
   * Turns on or turns off the validation of the uploaded file during moving it to a new location.
   *
   * @var boolean $validate
   * @access public
   */
  public $validate = true;

  /**
   * Allowable file extensions.
   * The uploaded file should have extension which matches to one of these file extensions.
   *
   * @var array $extensions
   * @access public
   */
  public $extensions = [];
  
  /**
   * Allowable mime types.
   * The uploaded file should have mime type which matches to one of these types.
   *
   * @var array $types
   * @access public
   */
  public $types = [];
  
  /**
   * The maximum file size (in bytes) of the uploaded file.
   * It should be greater than 0.
   *
   * @var integer $max
   * @access public
   */
  public $max = null;
  
  /**
   * The minimum file size (in bytes) of the uploaded file.
   * It should be greater than 0.
   *
   * @var integer $min
   * @access public
   */
  public $min = null;

  /**
   * Constructor. Accepts the information of the uploaded file as provided by the PHP global $_FILES.
   *
   * @param string|array - the full temporary path to the file or data array of the uploaded file from $_FILES.
   * @param string $name - the original file name.
   * @param string $type - the original file mime type.
   * @param integer $size - the file size (in bytes).
   * @param integer $error - the upload error.
   * @access public
   */
  public function __construct($path, $name = null, $type = null, $size = null, $error = null)
  {
    if (is_array($path))
    {
      $this->data = ['name' => $path['name'],
                     'path' => $path['tmp_name'],
                     'type' => $path['type'] ?: 'application/octet-stream',
                     'size' => (int)$path['size'],
                     'error' => (int)$path['error']];
    }
    else
    {
      $this->data = ['name' => $name,
                     'path' => $path,
                     'type' => $type ?: 'application/octet-stream',
                     'size' => (int)$size,
                     'error' => (int)$error];
    }
  }
  
  /**
   * Returns the extension based on the mime type.
   * If the mime type is unknown, returns NULL.
   *
   * @return string|null
   * @access public
   */
  public function getExtensionByType()
  {
    $type = $this->getType();
    return isset($this->defaultExtensions[$type]) ? $this->defaultExtensions[$type] : null;
  }
  
  /**
   * Returns the extension of the uploaded file.
   *
   * @return string
   * @access public
   */
  public function getExtension()
  {
    return pathinfo($this->data['name'], PATHINFO_EXTENSION);
  }
  
  /**
   * Returns the real mime type of the uploaded file.
   * If the mime type is unknown, returns NULL.
   *
   * @return string|null
   * @access public
   */
  public function getType()
  {
    return $this->data['path'] ? mime_content_type($this->data['path']) : null;
  }
  
  /**
   * Returns the real size (in bytes) of the uploaded file.
   *
   * @return integer
   * @access public
   */
  public function getSize()
  {
    return filesize($this->data['path']);
  }
  
  /**
   * Returns the original name of the uploaded file.
   *
   * @return string
   * @access public
   */
  public function getClientName()
  {
    return $this->data['name'];
  }
  
  /**
   * Returns the original mime type of the uploaded file.
   *
   * @return string
   * @access public
   */
  public function	getClientType()
  {
    return $this->data['type'];
  }

  /**
   * Returns the original size (in bytes) of the uploaded file.
   *
   * @return integer
   * @access public
   */
  public function getClientSize()
  {
    return $this->data['size'];
  }
  
  /**
   * Returns the code of the last happened error.
   *
   * @return integer
   * @access public
   */
  public function getErrorCode()
  {
    return $this->error;
  }
  
  /**
   * Returns the message of the last happened error.
   *
   * @return string
   * @access public
   */
  public function getErrorMessage()
  {
    switch ($this->error)
    {
      case 0:
        $message = 'File has been successfully uploaded.';
        break;
      case UPLOAD_ERR_INI_SIZE:
        $message = 'The uploaded file exceeds the upload_max_filesize (' . ini_get('upload_max_filesize') . ') directive in php.ini.';
        break;
      case UPLOAD_ERR_FORM_SIZE:
        $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
        break;
      case UPLOAD_ERR_PARTIAL:
        $message = 'The uploaded file was only partially uploaded.';
        break;
      case UPLOAD_ERR_NO_FILE:
        $message = 'No file was uploaded.';
        break;
      case UPLOAD_ERR_NO_TMP_DIR:
        $message = 'Missing a temporary folder.';
        break;
      case UPLOAD_ERR_CANT_WRITE:
        $message = 'Failed to write file to disk.';
        break;
      case UPLOAD_ERR_EXTENSION:
        $message = 'File upload stopped by extension.';
        break;
      case 10:
        $message = 'Only the following file extensions are allowed: ' . implode(', ', $this->extensions);
        break;
      case 11:
        $message = 'Only the following file mime types are allowed: ' . implode(', ', $this->types);
        break;
      case 12:
        $message = 'The file size should not exceed ' . number_format($this->max / (1024 * 1024), 2) . 'M';
        break;
      case 13:
        $message = 'The file size should not be less than ' . number_format($this->min / (1024 * 1024), 2) . 'M';
        break;
      case 14:
        $message = 'The file has not been uploaded via HTTP.';
        break;
      case 15:
        $message = 'The destination directory is not writable.';
        break;
      case 16:
        $message = 'Cannot move uploaded file to the destination directory.';
        break;
      default:
        $message = 'File uploading is failed (error code is ' . $this->error . ').';
        break;
    }
    return $message;
  }
  
  /**
   * Checks that the uploaded file was uploaded successfully and match the specified requirements.
   * Returns TRUE if no error occurred during uploading and all requirements are met and FALSE otherwise.
   *
   * @return boolean
   * @access public
   */
  public function validate()
  {
    if ($this->data['error'] > 0)
    {
      $this->error = $this->data['error'];
      return false;
    }
    if (is_array($this->extensions) && count($this->extensions) > 0)
    {
      if (!in_array($this->getExtension(), $this->extensions))
      {
        $this->error = 10;
        return false;
      }
    }
    if (is_array($this->types) && count($this->types) > 0)
    {
      if (!in_array($this->getType(), $this->types))
      {
        $this->error = 11;
        return false;
      }
    }
    if ($this->max > 0 || $this->min > 0) $size = $this->getSize();
    if ($this->max > 0)
    {
      if ($size > $this->max)
      {
        $this->error = 12;
        return false;
      }
    }
    if ($this->min > 0)
    {
      if ($size < $this->min)
      {
        $this->error = 13;
        return false;
      }
    }
    $this->error = 0;
    return true;
  }
  
  /**
   * Moves the uploaded file to a new location.
   * Returns the information of the moved file and FALSE if an error was occurred.
   *
   * @return array|boolean
   * @access public
   */
  public function move()
  {
    if ($this->validate && !$this->validate()) return false;
    if (!is_uploaded_file($this->data['path']))
    {
      $this->error = 14;
      return false;
    }
    $file = $this->destination;
    if (substr($file, -1) != '/') $file .= '/';
    if (!is_dir($file)) mkdir($file, 0775, true);
    if (!is_writable($file) && !chmod($file, 0775))
    {
      $this->error = 15;
      return false;
    }
    $ext = $this->getExtension() ?: $this->getExtensionByType();
    if (!$this->unique) $name = $this->name ?: $this->data['name'];
    else $name = md5(microtime() . mt_rand()) . ($ext ? '.' . $ext : '');
    $file .= $name;
    if (!move_uploaded_file($this->data['path'], $file))
    {
      $this->error = 16;
      return false;
    }
    return ['path' => $file, 'name' => $name, 'extension' => $ext, 'originalName' => $this->data['name'], 'size' => filesize($file), 'type' => mime_content_type($file)];
  }
}