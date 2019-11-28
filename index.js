/*jslint long, node*/

(function () {

    "use strict";

    var active;
    var backend;
    var browser;
    var calling;
    var check;
    var cleanEInk;
    var current;
    var dispatch;
    var download;
    var drawEInk;
    var drawing;
    var init;
    var path;
    var playing;
    var save;
    var stop;
    var stopCalling;
    var task;
    var template;
    var website;

    //-------------------------------------------------------------------------

    var connect = require("socket.io-client");
    var express = require("express");
    var fs = require("fs");
    var http = require("http");
    var macaddress = require("macaddress");
    var network = require("network");
    var omx = require("omxplayer-controll");
    var qrcode = require("qrcode");
    var request = require("request");
    var socket = require("socket.io");
    var subprocess = require("child_process");

    //-------------------------------------------------------------------------

    var command = "/home/pi/tmp/command.sh";
    var config = "/home/pi/tmp/config";
    var filesite = false;
    var home = "/home/pi/client/board/";
    var snapshot = "/home/pi/tmp/snapshot.png";
    var tmp = "/tmp/";
    var trigger = "/home/pi/tmp/trigger";

    //-------------------------------------------------------------------------

    var app = express();
    var queue = [];
    var server = http.createServer(app);
    var io = socket(server);

    //-------------------------------------------------------------------------

    active = function (token, data) {
        var settings = JSON.parse(fs.readFileSync(config, "UTF-8"));

        if (settings.token !== token) {
            settings.token = token;

            fs.writeFileSync(config, JSON.stringify(settings), "UTF-8");
        }

        backend = connect("http://" + settings.domain + ":" + settings.port + "/?network=" + Buffer.from(JSON.stringify({
            "address": data.ip_address,
            "gateway": data.gateway_ip,
            "netmask": data.netmask,
            "token": token
        })).toString("base64"));

        backend.on("CALLING", function (info) {
            if (browser) {
                browser.emit("CALLING", info);
            }
        });

        backend.on("COMMAND", function (command) {
            subprocess.exec(command, function (error, stdout, stderr) {
                backend.emit("COMMAND", {
                    "error": error,
                    "stdout": stdout,
                    "stderr": stderr
                });
            });
        });

        backend.on("E-INK", function (info) {
            if (info.image) {
                var file = home + "files/" + info.image;

                if (fs.existsSync(file)) {
                    drawEInk(info.type, file);
                } else {
                    download("e-ink", info.image, file, function () {
                        drawEInk(info.type, file);
                    });
                }
            } else {
                cleanEInk(info.type);
            }
        });

        backend.on("HOST", function (host) {
            var cfg = JSON.parse(fs.readFileSync(config, "UTF-8"));

            cfg.domain = host.domain;
            cfg.path = host.path;
            cfg.port = host.port;

            qrcode.toFile(home + "backend.png", "https://" + cfg.domain + cfg.path + "backend/", function () {
                fs.writeFileSync(config, JSON.stringify(cfg), "UTF-8");
                fs.writeFileSync(command, "/home/pi/client/bin/update_client.sh\nsudo reboot");
            });
        });

        backend.on("PROGRAMS", dispatch);

        backend.on("REBOOT", function () {
            fs.writeFileSync(command, "sudo reboot");
        });

        backend.on("SNAPSHOT", function (filename) {
            fs.writeFileSync(trigger, filename);
        });

        backend.on("STOP_CALLING", stopCalling);

        backend.on("WEBSITE", function (url) {
            website = url;
        });
    };

    //-------------------------------------------------------------------------

    check = function (type) {
        omx.getPosition(function (error) {
            if (playing) {
                if (error) {
                    playing = false;

                    if (browser) {
                        browser.emit("FINISH", type);
                    }
                } else {
                    setTimeout(function () {
                        check(type);
                    }, 1000);
                }
            }
        });
    };

    //-------------------------------------------------------------------------

    cleanEInk = function (type) {
        if (drawing) {
            return;
        }
    };

    //-------------------------------------------------------------------------

    dispatch = function (bundle) {
        var background;
        var content;
        var digest;
        var disabled;
        var program;

        if (bundle) {
            current = bundle;
        }

        if (browser && current) {
            if (current.data && current.data[0].programs) {
                program = current.data[0].programs.filter(function (item) {
                    if (item.begin_time <= current.time) {
                        if (!item.end_time || current.time <= item.end_time) {
                            return true;
                        }
                    }

                    return false;
                });
            } else {
                program = [];
            }

            if (program.length) {
                program = program.pop();
            } else {
                program = {
                    "template": {}
                };
            }

            digest = JSON.stringify(program.template);

            if (template !== digest) {
                template = digest;

                background = "";

                if (program.template.bg_color) {
                    background += "#main { background-color: " + program.template.bg_color + "; }\n";
                }

                if (program.template.bg_image) {
                    background += "#main { background-image: url(" + path(program.template.bg_image) + "); }\n";
                }

                save("custom.css", program.template.style);
                save("program.css", background);
                save("custom.php", program.template.content);
                save("custom.js", program.template.script);

                browser.emit("RELOAD");
            } else {
                disabled = current.data && current.data[0].disabled;

                if (disabled) {
                    content = {};

                    Object.keys(program.content).forEach(function (type) {
                        var items = [];

                        program.content[type].forEach(function (item) {
                            if (disabled[item.category_id] && disabled[item.category_id][item.id]) {
                                return;
                            }

                            items.push(item);
                        });

                        content[type] = items;
                    });
                } else {
                    content = program.content;
                }

                save("content.json", JSON.stringify({
                    "date": current.date,
                    "time": current.time,
                    "href": website,
                    "counter_num": current.data && current.data[0].counter_num,
                    "content": content
                }));

                browser.emit("CONTENT");

                if (content && content.calling) {
                    content.calling.forEach(function (row) {
                        if (row.front_sound) {
                            download("calling", row.front_sound, home + "files/" + row.front_sound);
                        }

                        JSON.parse(row.voice_data).forEach(function (voice) {
                            if (typeof voice === "number") {
                                download("calling", voice, home + "files/" + voice);
                            }
                        });

                        if (row.rear_sound) {
                            download("calling", row.rear_sound, home + "files/" + row.rear_sound);
                        }
                    });
                }
            }
        }
    };

    //-------------------------------------------------------------------------

    download = function (category, id, destination, callback) {
        var file;
        var status;
        var url;

        if (category === "image") {
            url = website + "backend/index.php?p_action_name=get-file&width=1280&height=720&id=" + id;
        } else if (category === "calling") {
            url = website + "files/voices/" + id + ".mp3";
        } else {
            url = path(id, filesite);
        }

        if (!fs.existsSync(destination)) {
            if (task) {
                if (!download[id]) {
                    download[id] = true;

                    queue.push({
                        "category": category,
                        "id": id,
                        "destination": destination
                    });
                }
            } else {
                download[id] = true;
                file = tmp + id;
                task = fs.createWriteStream(file);

                task.on("close", function () {
                    var next = queue.shift();

                    delete download[id];

                    task = null;

                    if (status === 200) {
                        fs.renameSync(file, destination);

                        if (callback) {
                            callback();
                        }
                    }

                    if (next) {
                        download(next.category, next.id, next.destination);
                    }
                });

                request(url).on("response", function (response) {
                    status = response.statusCode;
                }).pipe(task);
            }
        }

        return url;
    };

    //-------------------------------------------------------------------------

    drawEInk = function (type, image) {
        if (drawing) {
            return;
        }

        drawing = subprocess.spawn("/usr/bin/python", ["/home/pi/client/e-ink/draw" + type + ".py", image]);

        drawing.on("close", function () {
            drawing = null;
        });
    };

    //-------------------------------------------------------------------------

    init = function () {
        macaddress.one("eth0", function (ignore, token) {
            if (token) {
                network.get_active_interface(function (error, data) {
                    if (error) {
                        setTimeout(init, 1000);
                    } else {
                        active(token, data);
                    }
                });
            } else {
                setTimeout(init, 1000);
            }
        });
    };

    //-------------------------------------------------------------------------

    path = function (id, prefix) {
        if (!prefix) {
            prefix = website + "files/";
        }

        return prefix + Math.floor(id / 1000) + "/" + id;
    };

    //-------------------------------------------------------------------------

    save = function (name, text) {
        fs.writeFileSync(home + name, text || "");
    };

    //-------------------------------------------------------------------------

    stop = function () {
        if (playing) {
            playing = false;

            omx.getDuration(function (error, duration) {
                if (error) {
                    omx.hideVideo();
                    omx.pause();
                } else {
                    omx.setPosition(duration);
                }
            });
        }
    };

    //-------------------------------------------------------------------------

    stopCalling = function () {
        if (calling) {
            calling.kill();
            calling = null;
        }
    };

    //-------------------------------------------------------------------------

    app.get("/calling", function (request, response) {
        if (browser) {
            browser.emit("CALLING", {"calling_num": request.query.num});
        }

        response.send("");
    });

    //-------------------------------------------------------------------------

    app.get("/setup-device", function (request, response) {
        if (backend) {
            backend.once("SETUP_RESULT", function (result) {
                if (result.success) {
                    var cfg = JSON.parse(fs.readFileSync(config, "UTF-8"));

                    qrcode.toFile(home + "register.png", "https://" + cfg.domain + "/r/?x=" + result.device.qr_code, function () {
                        response.send(JSON.stringify(result));
                    });
                } else {
                    response.send(JSON.stringify(result));
                }
            });

            backend.emit("SETUP", request.query.id);
        } else {
            response.send("");
        }
    });

    //-------------------------------------------------------------------------

    app.get("/snapshot-done", function (request, response) {
        if (backend && fs.existsSync(snapshot)) {
            backend.emit("SNAPSHOT", {
                "name": request.query.token,
                "content": fs.readFileSync(snapshot).toString("base64")
            });
        }

        response.send("");
    });

    //-------------------------------------------------------------------------

    app.get("/stop-calling", function (ignore, response) {
        stopCalling();

        response.send("");
    });

    //-------------------------------------------------------------------------

    io.on("connection", function (client) {
        if (browser) {
            client.disconnect();
            return;
        }

        browser = client;

        browser.on("disconnect", function () {
            browser = null;

            stop();
        });

        browser.on("CALLING", function (list) {
            subprocess.spawnSync("/home/pi/client/bin/merge.sh", list);

            calling = subprocess.spawn("/usr/bin/omxplayer.bin", ["/tmp/output_MP3WRAP.mp3"]);

            calling.on("close", function () {
                calling = null;

                browser.emit("FINISH_CALLING");
            });
        });

        browser.on("PATH", function (data) {
            var file = home + "files/" + data.id;

            if (fs.existsSync(file)) {
                fs.utimesSync(file, new Date(), new Date());

                data.file = file;
                data.url = "http://raspberrypi/files/" + data.id;
            } else {
                data.url = download(data.category, data.id, file);
            }

            browser.emit("PATH", data);
        });

        browser.on("PLAY", function (data) {
            var file = data.file || data.url;

            if (file) {
                playing = true;

                omx.open(file, {
                    "blackBackground": false,
                    "otherArgs": ["--win", data.left + "," + data.top + "," + data.right + "," + data.bottom]
                });

                setTimeout(function () {
                    check(data.type);
                }, 3000);
            }
        });

        browser.on("RESET", function () {
            var cfg = JSON.parse(fs.readFileSync(config, "UTF-8"));

            cfg.reset = true;

            fs.writeFileSync(config, JSON.stringify(cfg), "UTF-8");

            save("content.json", "{}");
            save("custom.css");
            save("custom.js");
            save("custom.php");
            save("program.css");

            request("http://10.3.2.1/configure.php");
        });

        browser.on("STOP", stop);

        browser.on("STOP_CALLING", stopCalling);

        dispatch();
    });

    //-------------------------------------------------------------------------

    server.listen(8000);

    //-------------------------------------------------------------------------

    init();

}());
