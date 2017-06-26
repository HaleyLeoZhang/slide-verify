#####@Author 云天河
#####@Desciption RSA算法，js与php结合实践
#####@Source 项目地址[RSA-js-php](https://github.com/HaleyLeoZhang/rsa-js-php)

##RSA简介
RSA公钥加密算法是1977年由Ron Rivest、Adi Shamirh和LenAdleman在（美国麻省理工学院）开发的。RSA取名来自开发他们三者的名字。<br>
RSA是目前最有影响力的公钥加密算法，它能够抵抗到目前为止已知的所有密码攻击，已被ISO推荐为公钥数据加密标准。<br>
目前该加密方式广泛用于网上银行、数字签名等场合。<br>
RSA算法基于一个十分简单的数论事实：<font color='red'>将两个大素数相乘十分容易，但那时想要对其乘积进行因式分解却极其困难，因此可以将乘积公开作为加密密钥。</font>

##算法核心

    RSA的算法涉及三个参数，n、e1、e2。
    
    其中，n是两个大质数p、q的积，n的二进制表示时所占用的位数，就是所谓的密钥长度。
    
    e1和e2是一对相关的值，e1可以任意取，但要求e1与(p-1)*(q-1)互质；再选择e2，要求(e2*e1)mod((p-1)*(q-1))=1。
    
    （n，e1),(n，e2)就是密钥对。其中(n，e1)为公钥，(n，e2)为私钥。[1]  
    
    RSA加解密的算法完全相同，设A为明文，B为密文，则：A=B^e2 mod n；B=A^e1 mod n；（公钥加密体制中，一般用公钥加密，私钥解密）
    
    e1和e2可以互换使用，即：
    
    A=B^e1 mod n；B=A^e2 mod n;

更详细的讲解请到 [阮一峰RSA详解](http://www.ruanyifeng.com/blog/2013/07/rsa_algorithm_part_two.html) 去看详情

##web中的应用

>分为两块
>>前端公钥加密<br>
>>后端私钥解密

云天河作为一个phper，本次主要讲解js+php实现此次的加密解密。

####获取公钥与私钥
[支付宝公钥私钥生成器](https://os.alipayobjects.com/download/secret_key_tools_RSA_win.zip?spm=a219a.7629140.0.0.qFVp7d&file=secret_key_tools_RSA_win.zip)
<br>
下载后，里面有有关使用说明
使用后，生成器生成的文件如下

    rsa_private_key.pem //私钥文件存这里的 <br>
    rsa_public_key.pem  //公钥在这里<br>
    rsa_private_key_pkcs8.pem //这个文件，php用不上的

运行bulid_js.php生成js

写入私钥到php，公钥到js中<br>
<font color='red'>私钥公钥，原来是几行就是几行，别自己去合成一行</font>
如，原本的公钥：

    -----BEGIN PUBLIC KEY-----
    MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6KzAVhTxDl/6EUTtCbtRFOPKA
    4/WOD9WOSP+vxIa7+wjHnNXtWWf2JuzlTapHrx++J8K9zn75tGibXHsZb/DHvp4P
    l50Ln2w1VhYuwg2MAUuf/Q2c8dIhM8srRmPGqEn621GTK0cNGweyLR1y88epLSt6
    MnbQAY89vGVd/LR5TwIDAQAB
    -----END PUBLIC KEY-----
错误的引入方法：

    var public_key='-----BEGIN PUBLIC KEY-----MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6KzAVhTxDl/6EUTtCbtRFOPKA4/WOD9WOSP+vxIa7+wjHnNXtWWf2JuzlTapHrx++J8K9zn75tGibXHsZb/DHvp4Pl50Ln2w1VhYuwg2MAUuf/Q2c8dIhM8srRmPGqEn621GTK0cNGweyLR1y88epLSt6MnbQAY89vGVd/LR5TwIDAQAB-----END PUBLIC KEY-----';

####JS部分引入rsa.js类库，用法如下

    <script type="text/javascript" src="http://libs.baidu.com/jquery/1.9.0/jquery.js"></script>
    <script src="./rsa.js"></script>
    <script>
        var public_key,encrypt,After_enode;
        post_data='云天河';//post的某条数据
        public_key='-----BEGIN PUBLIC KEY-----'
        +'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6KzAVhTxDl/6EUTtCbtRFOPKA'
        +'4/WOD9WOSP+vxIa7+wjHnNXtWWf2JuzlTapHrx++J8K9zn75tGibXHsZb/DHvp4P'
        +'l50Ln2w1VhYuwg2MAUuf/Q2c8dIhM8srRmPGqEn621GTK0cNGweyLR1y88epLSt6'
        +'MnbQAY89vGVd/LR5TwIDAQAB-----END PUBLIC KEY-----';
        encrypt = new JSEncrypt();
        encrypt.setPublicKey( public_key );//传入公钥
        After_enode = encrypt.encrypt( post_data );//传入准备post的数据
        // js解密方法，大致如下，此次不作更多说明
        /**
        var decrypt = new JSEncrypt();
        decrypt.setPrivateKey('这里填写私钥');
        var uncrypted = decrypt.decrypt(After_enode);
        */
         document.write("加密前的数据:<input type='text' value=" + post_data+'><br><br>');
         document.write('加密后的数据:<textarea rows="4" cols="50">' + After_enode + "</textarea><br><br>");
         $.ajax({
            url:'./rsa.php',
            data:{"name":After_enode},
            dataType:'html',
            type:'post',
            success:function(html){
                $('body').append(html);
            }
         });
    </script>
    
####PHP部分
众所周知,php是用C语言写的,<br>
所以算法的实现，还是用php的c扩展实现比较合理，示例如下

    <?php
    /**
    * RSA私钥解密，需在php.ini开启php_openssl.dll扩展
    * @param after_encode_data 前端传来，经 RSA 加密后的数据
    * @return 返回解密后的数据
    */
    function RSA_Decode($after_encode_data){
        $private_key='-----BEGIN RSA PRIVATE KEY-----
    MIICXQIBAAKBgQC6KzAVhTxDl/6EUTtCbtRFOPKA4/WOD9WOSP+vxIa7+wjHnNXt
    WWf2JuzlTapHrx++J8K9zn75tGibXHsZb/DHvp4Pl50Ln2w1VhYuwg2MAUuf/Q2c
    8dIhM8srRmPGqEn621GTK0cNGweyLR1y88epLSt6MnbQAY89vGVd/LR5TwIDAQAB
    AoGAWD1WKi0flk45pc+2zdMoK7NFRhBGeFJK/4jcIBx/XCQtUielQj2pSAPFLx5z
    wkxgOEoyRLLWflajalgYRMNJFSSZA9tCPmIID32OYmVm+ChCt5sTxvrugzDvA8zV
    z/p97Kbz1/8BezTa4fWOfvrmPH0JrOkVcTJYpu5WlDVcf9ECQQDnVVlKccb/a8us
    71FIVCZo6gBnwBf9sVeEj2WVIQdrzIYVQfVMguTiDSL0GT6FonL84XTNM8kJOYpw
    G9mq9GCXAkEAzgT9Tm3aRMAG+33pCjED05za1OwwXf3xSeFNH4p9PMEsga/cew8R
    pZcfC+qLj/t/yiDhf5TpHytJzQ20g9oMCQJAMYNAAEIH8KVWy6XRROTV78Cd45bm
    y6LIc5PpjxipqPX2gNhEM2MUsBlVsN8yVZHmgJ+Uy1LZJYNOUR504TU68wJBAIUx
    UJreBpkgFOOO+ZTvL2wmIow5zuNVhCOhl3zmyiT3NtD5Y2/jxCLsWtQXZPdHP8zs
    CR20pirSj7oUPDpqRBECQQCANhG5Oo8eP0CU0Ruik7GmA6RuLbryEtCc3urf1VEp
    /ebhi8ynGyC8FNxwUe+kqYwJHNvkU8WqkxhSoPsU4+WO
    -----END RSA PRIVATE KEY-----';
        //这里是个范例，但是平时都是采用直接读取私钥文件的方式
        //$private_key = file_get_contents('./rsa_private_key.pem');
        openssl_private_decrypt(base64_decode($after_encode_data),$decode_result,$private_key); 
        return $decode_result;

    }
    
    var_dump(  RSA_Decode($_POST['name'])  ); 
