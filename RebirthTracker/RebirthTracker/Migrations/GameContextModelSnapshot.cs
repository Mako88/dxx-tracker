﻿// <auto-generated />
using System;
using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Infrastructure;
using Microsoft.EntityFrameworkCore.Storage.ValueConversion;
using RebirthTracker;

namespace RebirthTracker.Migrations
{
    [DbContext(typeof(GameContext))]
    partial class GameContextModelSnapshot : ModelSnapshot
    {
        protected override void BuildModel(ModelBuilder modelBuilder)
        {
#pragma warning disable 612, 618
            modelBuilder
                .HasAnnotation("ProductVersion", "3.1.6");

            modelBuilder.Entity("RebirthTracker.Game", b =>
                {
                    b.Property<long>("InternalID")
                        .ValueGeneratedOnAdd()
                        .HasColumnType("INTEGER");

                    b.Property<bool>("Archived")
                        .HasColumnType("INTEGER");

                    b.Property<byte[]>("Blob")
                        .HasColumnType("BLOB");

                    b.Property<int>("DescentVersion")
                        .HasColumnType("INTEGER");

                    b.Property<int>("Difficulty")
                        .HasColumnType("INTEGER");

                    b.Property<ushort>("GameID")
                        .HasColumnType("INTEGER");

                    b.Property<int>("GameMode")
                        .HasColumnType("INTEGER");

                    b.Property<string>("Header")
                        .HasColumnType("TEXT");

                    b.Property<string>("HostString")
                        .HasColumnType("TEXT");

                    b.Property<byte[]>("IPAddressData")
                        .HasColumnType("BLOB");

                    b.Property<DateTime>("LastUpdated")
                        .HasColumnType("TEXT");

                    b.Property<uint>("LevelNumber")
                        .HasColumnType("INTEGER");

                    b.Property<byte>("MaxPlayers")
                        .HasColumnType("INTEGER");

                    b.Property<string>("MissionName")
                        .HasColumnType("TEXT");

                    b.Property<string>("MissionTitle")
                        .HasColumnType("TEXT");

                    b.Property<string>("Name")
                        .HasColumnType("TEXT");

                    b.Property<byte>("NumConnected")
                        .HasColumnType("INTEGER");

                    b.Property<int>("Port")
                        .HasColumnType("INTEGER");

                    b.Property<string>("Status")
                        .HasColumnType("TEXT");

                    b.Property<string>("VersionString")
                        .HasColumnType("TEXT");

                    b.HasKey("InternalID");

                    b.ToTable("Games");
                });
#pragma warning restore 612, 618
        }
    }
}
