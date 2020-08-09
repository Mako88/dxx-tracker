using System;
using Microsoft.EntityFrameworkCore.Migrations;

namespace RebirthTracker.Migrations
{
    public partial class Initial : Migration
    {
        protected override void Up(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.CreateTable(
                name: "Games",
                columns: table => new
                {
                    ID = table.Column<ushort>(nullable: false),
                    Header = table.Column<string>(nullable: true),
                    Blob = table.Column<byte[]>(nullable: true),
                    LastUpdated = table.Column<DateTime>(nullable: false),
                    Port = table.Column<int>(nullable: false),
                    IPAddressData = table.Column<byte[]>(nullable: true),
                    HostString = table.Column<string>(nullable: true),
                    GameMode = table.Column<int>(nullable: false),
                    VersionString = table.Column<string>(nullable: true),
                    LevelNumber = table.Column<uint>(nullable: false),
                    Difficulty = table.Column<int>(nullable: false),
                    Status = table.Column<string>(nullable: true),
                    NumConnected = table.Column<byte>(nullable: false),
                    MaxPlayers = table.Column<byte>(nullable: false),
                    Name = table.Column<string>(nullable: true),
                    MissionTitle = table.Column<string>(nullable: true),
                    MissionName = table.Column<string>(nullable: true)
                },
                constraints: table =>
                {
                    table.PrimaryKey("PK_Games", x => x.ID);
                });
        }

        protected override void Down(MigrationBuilder migrationBuilder)
        {
            migrationBuilder.DropTable(
                name: "Games");
        }
    }
}
