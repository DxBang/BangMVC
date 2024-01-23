'use strict';
var window.crypto = window.crypto || window.msCrypto;

var text = '<?= $text ?>';
var key = '<?= $key ?>';
var iv = '<?= $iv ?>';
var enc_test = '<?= $enc ?>';

var enc = CryptoJS.AES.encrypt(text, CryptoJS.enc.Utf8.parse(key), { iv: CryptoJS.enc.Utf8.parse(iv) });

var r1 = enc.ciphertext.toString(); // 3fcba13193d461a53df938104ee6cf6d3b2061460b1f12376bf59bc255859d7f
var r2 = CryptoJS.enc.Base64.stringify(enc.ciphertext) // P8uhMZPUYaU9+TgQTubPbTsgYUYLHxI3a/WbwlWFnX8=


var dec = CryptoJS.AES.decrypt(enc, CryptoJS.enc.Utf8.parse(key), { iv: CryptoJS.enc.Utf8.parse(iv) });
var dec_text = CryptoJS.enc.Utf8.stringify(dec);
var dec_test = CryptoJS.AES.decrypt(enc_test, CryptoJS.enc.Utf8.parse(key), { iv: CryptoJS.enc.Utf8.parse(iv) });
var dec_test_text = CryptoJS.enc.Utf8.stringify(dec_test);



class bCrypto {
	static iv(iv) {
		try {
			if (iv !== undefined) {
				if (iv.length != 16)
					throw 'incorrect length for iv';
				this._iv = iv;
			}
			return this._iv;
		} catch (err) {
			console.error('iv error', err);
		}
	}
	static key(key) {
		try {
			if (key !== undefined) {
				if (key.length != 32)
					throw 'incorrect length for key';
				this._key = key;
			}
			return this._key;
		} catch (err) {
			console.error('key error', err);
		}
	}
	/*/
		CryptoJS.AES.decrypt('U2FsdGVkX1/5LLkFkTpawh1im4a/fCco5hS42cjn/fg=', 'Secret Passphrase').toString(CryptoJS.enc.Utf8);
	*/
	static encrypt(input, key, iv) {
		try {
			if (input === undefined) throw 'missing input';
			if (key !== undefined) this->key(key);
			if (iv !== undefined) this->iv(iv);
			if (this.iv() === undefined) throw 'missing iv';
			if (this.key() === undefined) throw 'missing key';
			let enc = CryptoJS.AES.encrypt(input, CryptoJS.enc.Utf8.parse(this.key()), { iv: CryptoJS.enc.Utf8.parse(this.iv()) });
			if (enc.ciphertext.toString()) {
				return CryptoJS.enc.Base64.stringify(enc.ciphertext);
			}

		} catch (err) {
			console.error('encrypt error', err);
		}
	}
	static decrypt(input, key, iv) {
		try {
			if (input === undefined) throw 'missing input';
			if (key !== undefined) this->key(key);
			if (iv !== undefined) this->iv(iv);
			if (this.iv() === undefined) throw 'missing iv';
			if (this.key() === undefined) throw 'missing key';
			let dec = CryptoJS.AES.decrypt(input, CryptoJS.enc.Utf8.parse(this.key()), { iv: CryptoJS.enc.Utf8.parse(this.iv()) });
			if (dec.toString()) {
				return CryptoJS.enc.Utf8.stringify(dec);
			}

		} catch (err) {
			console.error('decrypt error', err);
		}
	}
}

bCrypto.iv('<?= $iv ?>');
bCrypto.key('<?= $key ?>');
let e = bCrypto.encrypt('hello darkness, my old friend');
let d = bCrypto.decrypt(e);


class wCrypto {


}



/*
// Code goes here
var keySize = 256;
var ivSize = 128;
var iterations = 100;

var message = "Hello World";
var password = "Secret Password";


function encrypt (msg, pass) {
  var salt = CryptoJS.lib.WordArray.random(128/8);
  
  var key = CryptoJS.PBKDF2(pass, salt, {
      keySize: keySize/32,
      iterations: iterations
    });

  var iv = CryptoJS.lib.WordArray.random(128/8);
  
  var encrypted = CryptoJS.AES.encrypt(msg, key, { 
    iv: iv, 
    padding: CryptoJS.pad.Pkcs7,
    mode: CryptoJS.mode.CBC
    
  });
  
  // salt, iv will be hex 32 in length
  // append them to the ciphertext for use  in decryption
  var transitmessage = salt.toString()+ iv.toString() + encrypted.toString();
  return transitmessage;
}

function decrypt (transitmessage, pass) {
  var salt = CryptoJS.enc.Hex.parse(transitmessage.substr(0, 32));
  var iv = CryptoJS.enc.Hex.parse(transitmessage.substr(32, 32))
  var encrypted = transitmessage.substring(64);
  
  var key = CryptoJS.PBKDF2(pass, salt, {
      keySize: keySize/32,
      iterations: iterations
    });

  var decrypted = CryptoJS.AES.decrypt(encrypted, key, { 
    iv: iv, 
    padding: CryptoJS.pad.Pkcs7,
    mode: CryptoJS.mode.CBC
    
  })
  return decrypted;
}

var encrypted = encrypt(message, password);
var decrypted = decrypt(encrypted, password);

$('#encrypted').text("Encrypted: "+encrypted);
$('#decrypted').text("Decrypted: "+ decrypted.toString(CryptoJS.enc.Utf8) );
*/
