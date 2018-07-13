var canvasDraw=[
{
        name:'s1',
        cs:{
            width:100,
            height:100,
            radius:50
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x,params.y + params.radius);
            ctx.quadraticCurveTo(
                params.x - (params.radius * 2),
                params.y - (params.radius * 2),
                params.x,
                params.y - (params.radius / 1.5)
            );
            ctx.quadraticCurveTo(
                params.x + (params.radius * 2),
                params.y - (params.radius * 2),
                params.x,
                params.y + params.radius
            );
            ctx.closePath();
        }
    },{
        name:'s2',
        cs:{
            width:100,
            height:100
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x-params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y + params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y +params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y - params.height/2);
            ctx.closePath();
        }
    },{
        name:'s3',
        cs:{
            width:50,
            height:50,
            radius:50
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.arc(params.x,params.y,params.radius,0,Math.PI*2,true);
            ctx.closePath();
        }
    },{
        name:'s4',
        cs:{
            width:100,
            height:100,
            spacing:5
        },
        drawC:function(ctx,params) {
            ctx.beginPath();
            ctx.moveTo(params.x-params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x-params.spacing/2,params.y - params.height/2);
            ctx.lineTo(params.x-params.spacing/2,params.y - params.spacing/2);
            ctx.lineTo(params.x-params.width/2,params.y - params.spacing/2);
            ctx.moveTo(params.x+params.spacing/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.height/2);
            ctx.lineTo(params.x+params.width/2,params.y - params.spacing/2);
            ctx.lineTo(params.x+params.spacing/2,params.y - params.spacing/2);
            ctx.moveTo(params.x-params.width/2,params.y + params.spacing/2);
            ctx.lineTo(params.x-params.spacing/2,params.y +params.spacing/2);
            ctx.lineTo(params.x-params.spacing/2,params.y + params.height/2);
            ctx.lineTo(params.x-params.width/2,params.y + params.height/2);
            ctx.moveTo(params.x+params.spacing/2,params.y + params.spacing/2);
            ctx.lineTo(params.x+params.width/2,params.y +params.spacing/2);
            ctx.lineTo(params.x+params.width/2,params.y + params.height/2);
            ctx.lineTo(params.x+params.spacing/2,params.y + params.height/2);
        }
    }
];